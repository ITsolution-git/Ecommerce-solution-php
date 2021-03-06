<div class="row-fluid">
    <div class="col-lg-12">
        <section class="panel">
            <header class="panel-heading">
                Users
                <div class="pull-right">
                    <a class="btn btn-primary" href="/shopping-cart/users/download/">Download as CSV</a>
                </div>
            </header>

            <div class="panel-body">

                <div class="adv-table">
                    <table class="display table table-bordered table-striped" ajax="/shopping-cart/users/list-users/" perPage="30,50,100">
                        <thead>
                            <th>Email</th>
                            <th>Name</th>
                            <th>Date Created</th>
                        </thead>
                    </table>
                </div>
            </div>
        </section>
    </div>
</div>