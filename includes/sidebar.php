
<?php $current = basename($_SERVER['PHP_SELF']); ?>

<style>

/* ================= RESET ================= */

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
}

/* ================= SIDEBAR ================= */

.sidebar{
    position:fixed;
    top:0;
    left:0;
    width:250px;
    height:100vh;

    background:
        linear-gradient(
            180deg,
            #4338ca 0%,
            #4f46e5 40%,
            #6366f1 100%
        );

    padding:28px 18px;

    display:flex;
    flex-direction:column;

    box-shadow:
        10px 0 35px rgba(15,23,42,0.15);

    z-index:1000;

    overflow-y:auto;

    transition:transform 0.3s ease;
}

/* ================= LOGO ================= */

.sidebar-header{
    display:flex;
    align-items:center;
    justify-content:space-between;
    margin-bottom:30px;
}

.sidebar h2{
    color:#ffffff;
    font-size:24px;
    font-weight:700;
    letter-spacing:0.5px;
}

/* ================= BACK BUTTON ================= */

.back-btn{
    display:flex;
    align-items:center;
    justify-content:center;

    width:42px;
    height:42px;

    border-radius:12px;

    background:rgba(255,255,255,0.14);

    color:white;
    text-decoration:none;

    font-size:18px;
    font-weight:700;

    transition:0.25s ease;
}

.back-btn:hover{
    background:white;
    color:#4338ca;
    transform:translateX(-2px);
}

/* ================= NAVIGATION ================= */

.sidebar nav{
    display:flex;
    flex-direction:column;
    gap:12px;
}

/* ================= LINKS ================= */

.sidebar a.menu-link{
    position:relative;

    display:flex;
    align-items:center;
    gap:12px;

    padding:14px 16px;

    border-radius:16px;

    color:#eef2ff;
    text-decoration:none;

    font-weight:600;
    font-size:15px;

    transition:all 0.25s ease;

    background:rgba(255,255,255,0.06);

    overflow:hidden;
}

/* HOVER EFFECT */

.sidebar a.menu-link:hover{
    background:rgba(255,255,255,0.16);

    transform:translateX(4px);

    color:white;
}

/* ACTIVE */

.sidebar a.menu-link.active{
    background:white;
    color:#4338ca;

    box-shadow:
        0 10px 25px rgba(255,255,255,0.20);
}

/* ACTIVE BAR */

.sidebar a.menu-link.active::before{
    content:"";

    position:absolute;

    left:0;
    top:12px;

    width:4px;
    height:60%;

    border-radius:10px;

    background:#4338ca;
}

/* ================= ICON ================= */

.menu-icon{
    font-size:17px;
    width:22px;
    text-align:center;
}

/* ================= MENU TOGGLE ================= */

.menu-toggle{
    display:none;

    position:fixed;

    top:16px;
    left:16px;

    width:46px;
    height:46px;

    border:none;
    border-radius:14px;

    background:linear-gradient(
        135deg,
        #4f46e5,
        #6366f1
    );

    color:white;

    font-size:20px;

    cursor:pointer;

    z-index:1200;

    box-shadow:
        0 10px 25px rgba(79,70,229,0.30);

    transition:0.25s ease;
}

.menu-toggle:hover{
    transform:scale(1.05);
}

/* ================= OVERLAY ================= */

.overlay{
    display:none;

    position:fixed;

    top:0;
    left:0;

    width:100%;
    height:100%;

    background:rgba(15,23,42,0.45);

    backdrop-filter:blur(3px);

    z-index:900;
}

.overlay.active{
    display:block;
}

/* ================= SCROLLBAR ================= */

.sidebar::-webkit-scrollbar{
    width:6px;
}

.sidebar::-webkit-scrollbar-thumb{
    background:rgba(255,255,255,0.25);
    border-radius:10px;
}

/* ================= MOBILE ================= */

@media(max-width:768px){

    .sidebar{
        transform:translateX(-100%);
        width:260px;
    }

    .sidebar.active{
        transform:translateX(0);
    }

    .menu-toggle{
        display:flex;
        align-items:center;
        justify-content:center;
    }
}

</style>

<!-- ================= TOGGLE BUTTON ================= -->

<button class="menu-toggle" onclick="toggleSidebar()">
    ☰
</button>

<!-- ================= OVERLAY ================= -->

<div class="overlay" id="overlay" onclick="toggleSidebar()"></div>

<!-- ================= SIDEBAR ================= -->

<div class="sidebar" id="sidebar">

    <!-- HEADER -->

    <div class="sidebar-header">

        <h2>StudyPulse</h2>

    </div>

    <!-- MENU -->

    <nav>

        <?php if($_SESSION['role']=='admin'): ?>

            <a href="../admin/dashboard.php"
               class="menu-link <?php echo $current == 'dashboard.php' ? 'active' : ''; ?>">

                <span class="menu-icon"></span>
                Dashboard

            </a>

            <a href="../admin/manage_users.php"
               class="menu-link <?php echo $current == 'manage_users.php' ? 'active' : ''; ?>">

                <span class="menu-icon"></span>
                Users

            </a>

        <?php endif; ?>

        <?php if($_SESSION['role']=='professor'): ?>

            <a href="../professor/dashboard.php"
               class="menu-link <?php echo $current == 'dashboard.php' ? 'active' : ''; ?>">

                <span class="menu-icon"></span>
                Dashboard

            </a>

            <a href="../professor/create_quiz.php"
               class="menu-link <?php echo $current == 'create_quiz.php' ? 'active' : ''; ?>">

                <span class="menu-icon"></span>
                Create Quiz

            </a>

            <a href="../professor/upload_files.php"
               class="menu-link <?php echo $current == 'upload_files.php' ? 'active' : ''; ?>">

                <span class="menu-icon"></span>
                Upload Reviewer

            </a>

        <?php endif; ?>

        <?php if($_SESSION['role']=='student'): ?>

            <a href="../student/dashboard.php"
               class="menu-link <?php echo $current == 'dashboard.php' ? 'active' : ''; ?>">

                <span class="menu-icon"></span>
                Dashboard

            </a>

            <a href="../student/join_class.php"
               class="menu-link <?php echo $current == 'join_class.php' ? 'active' : ''; ?>">

                <span class="menu-icon"></span>
                Join Class

            </a>

        <?php endif; ?>

    </nav>

</div>

<script>

function toggleSidebar(){

    document
        .getElementById("sidebar")
        .classList
        .toggle("active");

    document
        .getElementById("overlay")
        .classList
        .toggle("active");
}

</script>
