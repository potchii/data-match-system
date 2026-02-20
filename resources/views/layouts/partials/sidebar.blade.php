<aside class="main-sidebar sidebar-dark-primary elevation-4">
    <a href="{{ route('dashboard') }}" class="brand-link">
        <span class="brand-text font-weight-light">Data Match System</span>
    </a>
    
    <div class="sidebar">
        <nav class="mt-2">
            <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
                <li class="nav-item">
                    <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-tachometer-alt"></i>
                        <p>Dashboard</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('upload.index') }}" class="nav-link {{ request()->routeIs('upload.*') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-upload"></i>
                        <p>Upload</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('results.index') }}" class="nav-link {{ request()->routeIs('results.*') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-list"></i>
                        <p>Match Results</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('batches.index') }}" class="nav-link {{ request()->routeIs('batches.*') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-history"></i>
                        <p>Batch History</p>
                    </a>
                </li>
            </ul>
        </nav>
    </div>
</aside>
