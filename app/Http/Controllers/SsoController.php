<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SsoController extends Controller
{
    public function loginWithToken(Request $request)
    {
        $plainToken = $request->query('token');

        if (!$plainToken) {
            abort(403, 'Missing token.');
        }

        $hashedToken = hash('sha256', $plainToken);

        $record = DB::table('sso_tokens')
            ->where('token', $hashedToken)
            ->where('used', false)
            ->where('expires_at', '>', now())
            ->first();

        if (!$record) {
            abort(403, 'Invalid or expired token.');
        }

        $user = User::where('email', $record->email)->first();

        if (!$user) {
            abort(404, 'User not found in Plan system.');
        }

        DB::table('sso_tokens')
            ->where('id', $record->id)
            ->update([
                'used' => true,
                'updated_at' => now(),
            ]);

        Auth::shouldUse('web');
        Auth::guard('web')->loginUsingId($user->id, true);

        $request->session()->put('login_web_' . sha1('App\Models\User'), $user->id);
        $request->session()->save();

        return redirect('/dashboard');
    }
}