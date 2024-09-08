<?php
include('authentication.php');
$page_title = "Dashboard";
include('includes/header.php');
include('includes/navbar.php');
?>
<div class="py-3">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center">
            <span>Hello, <?= $_SESSION['auth_user']['username']?>!</span>
            <a class="btn btn-dark btn-sm" href="notifications-page.php"><svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="currentColor" class="bi bi-bell-fill" viewBox="0 0 16 16">
            <path d="M8 16a2 2 0 0 0 2-2H6a2 2 0 0 0 2 2m.995-14.901a1 1 0 1 0-1.99 0A5 5 0 0 0 3 6c0 1.098-.5 6-2 7h14c-1.5-1-2-5.902-2-7 0-2.42-1.72-4.44-4.005-4.901"/>
            </svg></a>
        </div>
        <div class="card mt-3">
            <div class="card-body">
                <blockquote class="blockquote mb-0 font-sm">
                <p>A well-known quote, contained in a blockquote element.</p>
                <footer class="blockquote-footer">Someone famous in <cite title="Source Title">Source Title</cite></footer>
                </blockquote>
            </div>
        </div>
        <div class="card mt-2 text-center">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-borderless">
                        <thead>
                            <tr>
                                <th scope="col">Expenses</th>
                                <th scope="col">Income</th>
                                <th scope="col">Balance</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>0.00</td>
                                <td>0.00</td>
                                <td>0.00</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include('includes/footer.php') ?>