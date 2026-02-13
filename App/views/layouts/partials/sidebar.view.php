

    <!-- Sidebar -->
    <?php
        function isMenuActive($prefix) {
            return strpos($_SERVER['REQUEST_URI'], $prefix) !== false ? 'show' : '';
        }
        function isLinkActive($uri) {
            return strpos($_SERVER['REQUEST_URI'], $uri) !== false ? 'active text-primary' : '';
        }
    ?>

    <div class="bg-dark text-white vh-100 p-3">
        <h4 class="text-white mb-4">Admin Panel</h4>

        <!-- Dashboard -->
        <a href="/web/admin" class="nav-link text-white mb-2 <?= isLinkActive('/web/admin') ?>">
            <?=icon('speedometer2') ?> Dashboard
        </a>

        <!-- Students -->
         
        <div class="accordion" id="sidebarMenu">
            <div class="accordion-item bg-dark border-0 mb-0">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed bg-dark text-white" type="button" data-bs-toggle="collapse" data-bs-target="#studentsMenu">
                        <?=icon('people-fill') ?> Students
                    </button>
                </h2>
                <div id="studentsMenu" class="accordion-collapse collapse <?= isMenuActive('/web/students') ?> text-white" data-bs-parent="#sidebarMenu">
                    <div class="accordion-body p-0">
                        <a href="/web/students" class="nav-link ps-4 <?= isLinkActive('/web/students') ?>"> <?=icon('person-fill-gear')?> Manage Students</a>
                        <a href="/web/students/import" class="nav-link ps-4 <?= isLinkActive('/web/students/import') ?>"><?=icon('cloud-arrow-down-fill')?> Import</a>
                        <a href="/web/students/export" class="nav-link ps-4 <?= isLinkActive('/web/students/export') ?>"><?=icon('cloud-arrow-up-fill')?> Export</a>
                    </div>
                </div>
            </div>
       
            <!-- Staff -->
            <div class="accordion-item bg-dark border-0">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed bg-dark text-white" type="button" data-bs-toggle="collapse" data-bs-target="#staffMenu">
                        <?=icon('person-badge-fill') ?> Staff
                    </button>
                </h2>
                <div id="staffMenu" class="accordion-collapse collapse <?= isMenuActive('/web/staff') ?> text-white" data-bs-parent="#sidebarMenu">
                    <div class="accordion-body p-0">
                        <a href="/web/staff" class="nav-link ps-4 <?= isLinkActive('/web/staff') ?>">Manage Staff</a>
                        <a href="/web/staff/import" class="nav-link ps-4 <?= isLinkActive('/web/staff/import') ?>">Import</a>
                        <a href="/web/staff/export" class="nav-link ps-4 <?= isLinkActive('/web/staff/export') ?>">Export</a>
                    </div>
                </div>
            </div>

            <!-- Finance -->
            <div class="accordion-item bg-dark border-0">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed bg-dark text-white" type="button" data-bs-toggle="collapse" data-bs-target="#financeMenu">
                        <?=icon('cash-coin') ?> Finance
                    </button>
                </h2>
                <div id="financeMenu" class="accordion-collapse collapse <?= isMenuActive('/web/finance') ?> text-white" data-bs-parent="#sidebarMenu">
                    <div class="accordion-body p-0">
                        <a href="/web/finance/fees" class="nav-link ps-4 <?= isLinkActive('/web/finance/fees') ?>">Fees</a>
                        <a href="/web/finance/payments" class="nav-link ps-4 <?= isLinkActive('/web/finance/payments') ?>">Payments</a>
                        <a href="/web/finance/reports" class="nav-link ps-4 <?= isLinkActive('/web/finance/reports') ?>">Reports</a>
                    </div>
                </div>
            </div>

            <!-- Auth Management -->
            <div class="accordion-item bg-dark border-0">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed bg-dark text-white" type="button" data-bs-toggle="collapse" data-bs-target="#authMenu">
                        <?=icon('shield-lock-fill') ?> Auth
                    </button>
                </h2>
                <div id="authMenu" class="accordion-collapse collapse <?= isMenuActive('/web/admin') ?> text-white" data-bs-parent="#sidebarMenu">
                    <div class="accordion-body p-0">
                        <a href="/web/users" class="nav-link ps-4 <?= isLinkActive('/web/users') ?>">Users</a>
                        <a href="/web/roles" class="nav-link ps-4 <?= isLinkActive('/web/roles') ?>">Roles</a>
                        <a href="/web/permissions" class="nav-link ps-4 <?= isLinkActive('/web/permissions') ?>">Permissions</a>
                    </div>
                </div>
            </div>

            <!-- Settings -->
            <div class="accordion-item bg-dark border-0">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed bg-dark text-white" type="button" data-bs-toggle="collapse" data-bs-target="#settingsMenu">
                        <?=icon('gear-fill') ?> Settings
                    </button>
                </h2>
                <div id="settingsMenu" class="accordion-collapse collapse <?= isMenuActive('/web/settings') ?> text-white " data-bs-parent="#sidebarMenu">
                    <div class="accordion-body p-0">
                        <a href="/web/settings/general" class="nav-link ps-4 <?= isLinkActive('/web/settings/general') ?>">General</a>
                        <a href="/web/settings/profile" class="nav-link ps-4 <?= isLinkActive('/web/settings/profile') ?>">Profile</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Logout -->
        <a href="/web/logout" class="nav-link text-white mt-4">
            <?=icon('box-arrow-right') ?>  
        </a>
    </div>
