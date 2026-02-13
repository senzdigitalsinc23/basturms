<?php if(userCan('manage_students')) : ?>
<h2 class="mt-4">Welcome Back, Admin</h2>
<?php endif ?>
<div class="row my-4 mt-3">
    <div class="col-md-4">
        <div class="card text-bg-primary">
            <div class="card-body">
                <h5 class="card-title"><?=icon('people-fill') ?> Students</h5>
                <p class="card-text fs-4">1,200</p>
                <a href="/web/students" class="btn btn-sm btn-light">View All</a>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-bg-success">
            <div class="card-body">
                <h5 class="card-title"><?=icon('person-workspace') ?> Staff</h5>
                <p class="card-text fs-4">150</p>
                <a href="/web/staff" class="btn btn-sm btn-light">View All</a>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-bg-warning">
            <div class="card-body">
                <h5 class="card-title"><?=icon('currency-dollar') ?> Revenue</h5>
                <p class="card-text fs-4">$24,000</p>
                <a href="/web/finance" class="btn btn-sm btn-dark">View All</a>
            </div>
        </div>
    </div>
</div>

<div class="row my-4 mt-3" style="height: 50px;">
    <div class="col-lg-6 mb-4">
        <div class="card">
            <div class="card-header">Student Gender Distribution</div>
            <div class="card-body">
            <canvas id="genderChart"></canvas>
            </div>
        </div>
    </div>

    <div class="col-lg-6 mb-4">
        <div class="card">
            <div class="card-header">Monthly Logins</div>
            <div class="card-body">
            <canvas id="loginChart"></canvas>
            </div>
        </div>
    </div>
</div>


<script src="/assets/js/admin-dashboard-charts.js" defer></script>

