@extends('layouts.admin')

@section('title', 'Password Settings')

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">Password Settings</h1>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Update Password</h3>
                    </div>
                    <form method="POST" action="{{ route('user-password.update') }}">
                        @csrf
                        @method('PUT')
                        
                        <div class="card-body">
                            <div class="form-group">
                                <label for="current_password">Current Password</label>
                                <div style="position: relative;">
                                    <input type="password" class="form-control @error('current_password') is-invalid @enderror" 
                                           id="current_password" name="current_password" data-toggle-password required>
                                    @error('current_password')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="password">New Password</label>
                                <div style="position: relative;">
                                    <input type="password" class="form-control @error('password') is-invalid @enderror" 
                                           id="password" name="password" data-toggle-password required>
                                    @error('password')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="password_confirmation">Confirm Password</label>
                                <div style="position: relative;">
                                    <input type="password" class="form-control" 
                                           id="password_confirmation" name="password_confirmation" data-toggle-password required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary">Update Password</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<script src="{{ asset('js/password-visibility.js') }}"></script>
@endsection
