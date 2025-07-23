<nav class="navbar navbar-expand navbar-dark static-top">
    <a class="navbar-brand d-flex align-items-center" href="#"><img id="logo"
            src="{{ asset('images/school-logo.png') }}" />Jun-Ianez</a><button
        class="btn btn-link btn-sm text-white order-1 order-sm-0 ms-2" id="sidebarToggle" href="#"><i
            class="fa fa-bars"></i></button>

    <ul class="navbar-nav ms-auto ml-md-0">
        <li class="nav-item dropdown">
            <a href="#" class="nav-link dropdown-toggle" id="navbarDropdown" role="button" aria-haspopup="true"
                data-bs-toggle="dropdown" aria-expanded="false">
                <i class="fa fa-fw fa-plus"></i>
            </a>
            <div class="dropdown-menu" id="navbarDropdown" aria-labelledby="navbarDropdown">
                <h6 class="dropdown-header">Products</h6>
                <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#addProductModal">
                    <i class="fa fa-fw fa-tags"></i>
                    New Product
                </a>
                <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                    <i class="fa fa-fw fa-tag"></i>
                    New Product Category
                </a>

                <div class="dropdown-divider"></div>

                <h6 class="dropdown-header">Suppliers</h6>
                <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#addSupplierModal">
                    <i class="fa fa-fw fa-truck"></i>
                    New Supplier
                </a>
                <div class="dropdown-divider"></div>

                <h6 class="dropdown-header">Cashier</h6>
                <a href="/orders_create" class="dropdown-item">
                    <i class="fa fa-fw fa-shopping-cart"></i>
                    New Order
                </a>

                <div class="dropdown-divider"></div>


                <h6 class="dropdown-header">Inventory</h6>
                <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#addInventoryModal">
                    <i class="fa fa-fw fa-list-alt"></i>
                    New Inventory
                </a>

                <div class="dropdown-divider"></div>

                @if (Auth::check() && Auth::user()->hasRole('admin'))
                    <h6 class="dropdown-header">Users</h6>
                    <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#addUserModal">
                        <i class="fa fa-fw fa-user"></i>
                        New User
                    </a>
                @endif
            </div>
        </li>

        <li class="nav-item dropdown">
            <a href="#" class="nav-link dropdown-toggle" id="navbarDropdown" role="button" aria-haspopup="true"
                data-bs-toggle="dropdown" aria-expanded="false">
                <i class="fa fa-fw fa-sitemap"></i>
            </a>
            <div class="dropdown-menu" id="navbarDropdown" aria-labelledby="navbarDropdown">
                <h6 class="dropdown-header">Products</h6>
                <a href="/products" class="dropdown-item">
                    <i class="fa fa-fw fa-tags"></i>
                    All Products
                </a>
                <a href="/categories" class="dropdown-item">
                    <i class="fa fa-fw fa-tag"></i>
                    All Product Categories
                </a>

                <div class="dropdown-divider"></div>

                <h6 class="dropdown-header">Suppliers</h6>
                <a href="/suppliers" class="dropdown-item">
                    <i class="fa fa-fw fa-truck"></i>
                    All Suppliers
                </a>
                <div class="dropdown-divider"></div>

                <h6 class="dropdown-header">Cashier</h6>
                <a href="/orders" class="dropdown-item">
                    <i class="fa fa-fw fa-history"></i>
                    Orders History
                </a>
                <a href="/billings" class="dropdown-item">
                    <i class="fa fa-fw fa-receipt"></i>
                    Billing History
                </a>

                <div class="dropdown-divider"></div>

                <h6 class="dropdown-header">Inventory</h6>
                <a href="/inventories" class="dropdown-item">
                    <i class="fa fa-fw fa-list-alt"></i>
                    All Inventories
                </a>

                <div class="dropdown-divider"></div>

                @if (Auth::check() && Auth::user()->hasRole('admin'))
                    <h6 class="dropdown-header">Users</h6>
                    <a href="/users" class="dropdown-item">
                        <i class="fa fa-fw fa-users"></i>
                        All Users
                    </a>
                @endif

                <a class="dropdown-item" href="/logout"
                    onclick="return confirm('Are you sure you want to log out?')">
                    <i class="fa-solid fa-fw fa-power-off"></i>
                    Logout
                </a>
            </div>
        </li>

    </ul>
</nav>
