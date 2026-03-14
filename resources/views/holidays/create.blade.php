@extends('layouts.dashboard')

@section('content')

<div class="page-heading">
    <h3>Edit Holiday</h3>
</div>

<div class="page-content">
<section class="section">

<div class="card">
<div class="card-body">

<form method="POST" action="{{ route('holidays.update',$holiday->id) }}">
@csrf
@method('PUT')

@include('holidays.form')

<button class="btn btn-primary">
Update Holiday
</button>

<a href="{{ route('holidays.index') }}" class="btn btn-secondary">
Cancel
</a>

</form>

</div>
</div>

</section>
</div>

@endsection