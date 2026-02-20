@extends('layouts.admin')

@section('title', 'Two-Factor Authentication')

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">Two-Factor Authentication</h1>
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
                        <h3 class="card-title">Two-Factor Authentication Status</h3>
                    </div>
                    <div class="card-body">
                        @if($twoFactorEnabled)
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle"></i> Two-factor authentication is enabled.
                            </div>
                            
                            <form method="POST" action="{{ url('/user/two-factor-authentication') }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger">Disable Two-Factor Authentication</button>
                            </form>
                        @else
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i> Two-factor authentication is not enabled.
                            </div>
                            
                            <p>Add additional security to your account using two-factor authentication.</p>
                            
                            <form method="POST" action="{{ url('/user/two-factor-authentication') }}">
                                @csrf
                                <button type="submit" class="btn btn-primary">Enable Two-Factor Authentication</button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
