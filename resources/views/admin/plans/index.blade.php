@extends('layouts.admin')
@section('title', 'Plans')
@section('page-title', 'Subscription Plans')
@section('content')
<div class="row g-4">
    @foreach($plans as $plan)
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header py-3 px-4 d-flex align-items-center justify-content-between">
                <span class="fw-semibold">{{ $plan->name }}</span>
                <span class="badge" style="background:{{ $plan->status === 'active' ? 'rgba(25,135,84,0.2)' : 'rgba(220,53,69,0.2)' }};color:{{ $plan->status === 'active' ? '#6feaaa' : '#ff8a9a' }};border:1px solid {{ $plan->status === 'active' ? 'rgba(25,135,84,0.3)' : 'rgba(220,53,69,0.3)' }};">{{ ucfirst($plan->status) }}</span>
            </div>
            <div class="card-body p-4">
                <form method="POST" action="{{ route('admin.plans.update', $plan) }}">
                    @csrf @method('PUT')
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" style="font-size:0.8rem;">Plan Name</label>
                            <input type="text" name="name" class="form-control form-control-sm" value="{{ $plan->name }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" style="font-size:0.8rem;">Monthly Credits</label>
                            <input type="number" name="monthly_credits" class="form-control form-control-sm" value="{{ $plan->monthly_credits }}" min="0">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" style="font-size:0.8rem;">Monthly Price ($)</label>
                            <input type="number" name="price_monthly" step="0.01" class="form-control form-control-sm" value="{{ $plan->price_monthly }}" min="0">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" style="font-size:0.8rem;">Max API Keys</label>
                            <input type="number" name="max_api_keys" class="form-control form-control-sm" value="{{ $plan->max_api_keys }}" min="1">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" style="font-size:0.8rem;">Max Team Members</label>
                            <input type="number" name="max_team_members" class="form-control form-control-sm" value="{{ $plan->max_team_members }}" min="1">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" style="font-size:0.8rem;">Status</label>
                            <select name="status" class="form-select form-select-sm">
                                <option value="active" {{ $plan->status === 'active' ? 'selected' : '' }}>Active</option>
                                <option value="inactive" {{ $plan->status === 'inactive' ? 'selected' : '' }}>Inactive</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary btn-sm px-4">
                                <i class="fas fa-save me-1"></i>Save Plan
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endforeach
</div>
@endsection
