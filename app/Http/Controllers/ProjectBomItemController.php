<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectBomItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProjectBomItemController extends Controller
    {
        public function updateStatus(Request $request, Project $project, ProjectBomItem $item)
        {
            abort_unless($item->project_id === $project->id, 404);

            $data = $request->validate([
                'status' => ['required', 'in:not_ready,ready,done'],
            ]);

            $payload = [
                'status'     => $data['status'],
                'updated_by' => auth()->id(),
            ];

            if ($data['status'] === 'done') {
                $payload['done_at'] = now();
            } else {
                $payload['done_at'] = null;
            }

            $item->update($payload);

            return back()->with('success', 'BOM item status updated.');
        }

        public function reparse(Project $project)
        {
            if (!$project->bom_path || !Storage::disk('public')->exists($project->bom_path)) {
                return back()->withErrors(['bom' => 'No BOM PDF found to parse.']);
            }

            $project->bomItems()->delete();

            $this->parseBomPdfIntoItems($project);

            return back()->with('success', 'BOM re-parsed successfully.');
        }


        public function store(Request $request, Project $project)
{
    $data = $request->validate([
        'item'   => ['required', 'string', 'max:2000'],
        'status' => ['nullable', 'in:not_ready,ready,done'],
    ]);

    $status = $data['status'] ?? 'not_ready';

    $project->bomItems()->create([
        'section'    => 'MANUAL',
        'item'       => trim($data['item']),
        'status'     => $status,
        'done_at'    => $status === 'done' ? now() : null,
        'updated_by' => auth()->id(),
    ]);

    return back()->with('success', 'BOM item added.');
}

public function destroy(Project $project, ProjectBomItem $item)
{
    abort_unless(auth()->user()->can('bom_items.delete'), 403);

    abort_unless($item->project_id === $project->id, 404);

    $item->delete();

    return back()->with('success', 'BOM item deleted.');
}
        /**
         * Improved parser for your cutting-list style PDF.
         * Requires: composer require smalot/pdfparser
         */
        public function parseBomPdfIntoItems(Project $project): void
{
    $fullPath = Storage::disk('public')->path($project->bom_path);

    try {
        $parser = new \Smalot\PdfParser\Parser();
        $pdf    = $parser->parseFile($fullPath);
        $text   = (string) $pdf->getText();
    } catch (\Throwable $e) {
        $project->bomItems()->create([
            'section' => 'BOM',
            'item'    => 'BOM uploaded (unable to auto-parse)',
            'status'  => 'not_ready',
            'updated_by' => auth()->id(),
        ]);
        return;
    }

    $lines = collect(preg_split("/\r\n|\n|\r/", $text))
        ->map(fn ($l) => trim(preg_replace('/\s+/', ' ', $l)))
        ->filter(fn ($l) => $l !== '')
        ->values();

    $qtyPattern = '/\b\d+(?:\.\d+)?\s*(sqft|pcs|pc|m)\b/i';

    // section words
    $sectionWords = [
        'CUTTING LIST',
        'SCREWS',
        'WIRINGS',
        'LIGHTINGS',
        'ELECTRICAL ASSEMBLY',
        'POWER SUPPLY',
    ];

    $section = 'CUTTING LIST';
    $pendingItemLine = null;

    foreach ($lines as $line) {
        $upper = strtoupper($line);

        // ---- skip meta ----
        if (
            str_contains($upper, 'PROJECT NAME') ||
            str_contains($upper, 'ORDER NO') ||
            str_contains($upper, 'PREPARED BY') ||
            (str_contains($upper, 'PARTS') && str_contains($upper, 'MATERIALS')) ||
            $upper === 'OWNER :' ||
            $upper === 'LOCATION :' ||
            $upper === 'J.O NO :' ||
            str_starts_with($upper, 'SALES :')
        ) {
            continue;
        }

        $hasQty = preg_match($qtyPattern, $line);

        // ---- SECTION detect (flexible) ----
        // matches: "CUTTING LIST / BILL OF MATERIALS" even if split/extra spaces
        $isSectionLine = false;
        foreach ($sectionWords as $w) {
            if ($upper === $w || str_starts_with($upper, $w . ' ') || str_contains($upper, $w)) {
                $isSectionLine = true;
                $matched = $w;
                break;
            }
        }

        // if it's a section header and has NO qty => change section
        if ($isSectionLine && !$hasQty) {
            $section = $matched;
            $pendingItemLine = null;
            continue;
        }

        // ---- If line starts with a section word AND has qty, it's a REAL row ----
        // ex: "POWER SUPPLY 1pc 120w indoor power supply" OR "LIGHTINGS 15m"
        $startsWithSectionWord = false;
        $matchedSectionWord = null;

        foreach ($sectionWords as $w) {
            if (str_starts_with($upper, $w . ' ')) {
                $startsWithSectionWord = true;
                $matchedSectionWord = $w;
                break;
            }
        }

        if ($startsWithSectionWord && $hasQty) {
            $section = $matchedSectionWord;

            preg_match($qtyPattern, $line, $m);
            $qty = trim($m[0]);

            // if pending line exists, use it as item title (2-line pattern)
            if ($pendingItemLine) {
                $project->bomItems()->create([
                    'section'    => $section,
                    'item'       => $pendingItemLine,
                    'qty'        => $qty,
                    'status'     => 'not_ready',
                    'updated_by' => auth()->id(),
                ]);
                $pendingItemLine = null;
                continue;
            }

            // else use same line (remove section + qty)
            $clean = preg_replace('/^' . preg_quote($matchedSectionWord, '/') . '\s*/i', '', $line);
            $clean = trim(preg_replace($qtyPattern, '', $clean, 1));
            if ($clean === '') $clean = $line;

            $project->bomItems()->create([
                'section'    => $section,
                'item'       => $clean,
                'qty'        => $qty,
                'status'     => 'not_ready',
                'updated_by' => auth()->id(),
            ]);
            continue;
        }

        // ---- Normal row with qty ----
        if ($hasQty) {
            preg_match($qtyPattern, $line, $m);
            $qty = trim($m[0]);

            $cleanItem = trim(preg_replace($qtyPattern, '', $line, 1));
            if ($cleanItem === '') $cleanItem = $line;

            // if pending exists, pair qty with that pending item (2-line)
            if ($pendingItemLine) {
                $project->bomItems()->create([
                    'section'    => $section,
                    'item'       => $pendingItemLine,
                    'qty'        => $qty,
                    'status'     => 'not_ready',
                    'updated_by' => auth()->id(),
                ]);
                $pendingItemLine = null;
                continue;
            }

            $project->bomItems()->create([
                'section'    => $section,
                'item'       => $cleanItem,
                'qty'        => $qty,
                'status'     => 'not_ready',
                'updated_by' => auth()->id(),
            ]);
            continue;
        }

        // ---- no qty line => possible item name ----
        $noisy = [
            'FRONT COVER',
            'BACK COVER',
            'GFX MATERIALS SUBCONTRACTOR',
            'CUTTING CUTTING',
        ];

        if (!in_array($upper, $noisy, true) && strlen($line) >= 6) {
            // IMPORTANT: don't overwrite if we already have a pending line
            if (!$pendingItemLine) {
                $pendingItemLine = $line;
            } else {
                // if another line follows, append (some PDFs split item name)
                $pendingItemLine = trim($pendingItemLine . ' ' . $line);
            }
        }
    }

    if ($project->bomItems()->count() === 0) {
        $project->bomItems()->create([
            'section' => 'BOM',
            'item'    => 'BOM uploaded (no rows detected)',
            'status'  => 'not_ready',
            'updated_by' => auth()->id(),
        ]);
    }
}
    }