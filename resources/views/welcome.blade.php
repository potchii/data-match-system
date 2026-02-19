<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mason's Backend Test Bench</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Phase 2-4: Import & Match Test</h5>
                    </div>
                    <div class="card-body">
                        @if(session('success'))
                            <div class="alert alert-success">{{ session('success') }}</div>
                        @endif

                        @if($errors->any())
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    @foreach($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <form action="{{ route('upload.store') }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <div class="mb-3">
                                <label for="file" class="form-label">Select Excel/CSV File</label>
                                <input type="file" name="file" id="file" class="form-control" required>
                                <div class="form-text">Headers should match: regsno, surname, firstname, dob, etc.</div>
                            </div>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-success">Run Backend Engine</button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <div class="mt-4 text-center text-muted">
                    <small>Architect Mode: Checking UploadBatch, MainSystem, and MatchResult tables.</small>
                </div>
            </div>
        </div>
    </div>
</body>
</html>