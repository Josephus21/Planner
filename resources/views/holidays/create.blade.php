@extends('layouts.dashboard')


<div class="mb-3">
    <label class="form-label">Holiday Name</label>
    <input type="text" name="name" class="form-control" value="{{ old('name', $holiday->name ?? '') }}" required>
</div>

<div class="mb-3">
    <label class="form-label">Holiday Date</label>
    <input type="date" name="holiday_date" class="form-control" value="{{ old('holiday_date', isset($holiday) ? $holiday->holiday_date?->format('Y-m-d') : '') }}" required>
</div>

<div class="mb-3">
    <label class="form-label">Type</label>
    <select name="type" class="form-control" required>
        <option value="regular" {{ old('type', $holiday->type ?? '') === 'regular' ? 'selected' : '' }}>Regular</option>
        <option value="special" {{ old('type', $holiday->type ?? '') === 'special' ? 'selected' : '' }}>Special</option>
    </select>
</div>

<div class="mb-3">
    <label class="form-label">Company</label>
    <select name="company_id" class="form-control">
        <option value="">All Companies</option>
        @foreach($companies as $company)
            <option value="{{ $company->id }}" {{ (string) old('company_id', $holiday->company_id ?? '') === (string) $company->id ? 'selected' : '' }}>
                {{ $company->name }}
            </option>
        @endforeach
    </select>
</div>

<div class="form-check mb-2">
    <input type="hidden" name="is_recurring" value="0">
    <input type="checkbox" name="is_recurring" value="1" class="form-check-input" id="is_recurring"
        {{ old('is_recurring', $holiday->is_recurring ?? false) ? 'checked' : '' }}>
    <label for="is_recurring" class="form-check-label">Recurring every year</label>
</div>

<div class="form-check mb-3">
    <input type="hidden" name="is_active" value="0">
    <input type="checkbox" name="is_active" value="1" class="form-check-input" id="is_active"
        {{ old('is_active', $holiday->is_active ?? true) ? 'checked' : '' }}>
    <label for="is_active" class="form-check-label">Active</label>
</div>

<div class="mb-3">
    <label class="form-label">Notes</label>
    <textarea name="notes" class="form-control">{{ old('notes', $holiday->notes ?? '') }}</textarea>
</div>