<?php
// footer.php
?>

<footer class="footer mt-auto py-3 bg-dark text-light">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-6">
                <p class="mb-0">&copy; <?php echo date('Y'); ?> Inventory Management System. All rights reserved.</p>
            </div>
            <div class="col-md-6 text-md-end">
                <p class="mb-0">
                    <span class="text-light">Logged in as:
                        <?php if (isset($_SESSION['role'])): ?>
                            <span
                                class="badge bg-primary ms-2"><?php echo strtoupper(htmlspecialchars($_SESSION['role'])); ?>
                            </span>
                        <?php endif; ?>
                </p>
            </div>
        </div>
        <div class="row mt-2">
            <div class="col-12 text-center">
                <small class="text-light">
                    <i class="bi bi-clock"></i> Local Time: <span id="local-time"></span> |
                    <i class="bi bi-hdd"></i> Version: 1.0.0
                </small>
            </div>
        </div>
    </div>
</footer>

<script>
    function updateLocalTime() {
        const now = new Date();

        let hours = now.getHours();
        const minutes = String(now.getMinutes()).padStart(2, "0");
        const seconds = String(now.getSeconds()).padStart(2, "0");
        const ampm = hours >= 12 ? "PM" : "AM";

        hours = hours % 12;
        hours = hours ? hours : 12; // 0 → 12

        const formatted =
            (now.getMonth() + 1).toString().padStart(2, "0") + "/" +
            now.getDate().toString().padStart(2, "0") + "/" +
            now.getFullYear() + " " +
            hours + ":" + minutes + ":" + seconds + " " + ampm;

        document.getElementById("local-time").textContent = formatted;
    }

    // run immediately + every second
    updateLocalTime();
    setInterval(updateLocalTime, 1000);
</script>

<style>
    .footer {
        border-top: 1px solid #444;
        margin-top: 2rem;
        position: relative;
        bottom: 0;
        width: 100%;
    }

    .footer .badge {
        font-size: 0.7em;
    }

    .footer small {
        font-size: 0.85em;
    }

    .footer .bi {
        margin-right: 0.3rem;
    }
</style>