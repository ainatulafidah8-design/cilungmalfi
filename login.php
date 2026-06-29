<?php
include 'config.php';
if(isset($_SESSION['user_id'])) header("Location: index.php");
$error = '';
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    if($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
        header("Location: index.php");
        exit;
    } else $error = "Username atau password salah!";
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Cilung Malfi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
        *{font-family:'Poppins',sans-serif;}
        body{background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);min-height:100vh;display:flex;align-items:center;justify-content:center;margin:0;padding:20px;}
        .glass-card{background:rgba(255,255,255,0.2);backdrop-filter:blur(10px);border-radius:30px;border:1px solid rgba(255,255,255,0.3);box-shadow:0 25px 45px rgba(0,0,0,0.1);overflow:hidden;}
        .card-inner{background:rgba(255,255,255,0.9);border-radius:30px;padding:30px;}
        .btn-login{background:linear-gradient(45deg,#667eea,#764ba2);border:none;border-radius:50px;padding:12px;font-weight:600;}
        .btn-login:hover{transform:translateY(-3px);box-shadow:0 10px 20px rgba(0,0,0,0.2);}
        .form-control{border-radius:50px;padding:12px 20px;}
        .form-control:focus{border-color:#764ba2;box-shadow:none;}
        .logo-icon{font-size:60px;background:linear-gradient(45deg,#667eea,#764ba2);-webkit-background-clip:text;background-clip:text;color:transparent;}
    </style>
</head>
<body>
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="glass-card">
                <div class="card-inner">
                    <div class="text-center mb-4">
                        <i class="fas fa-utensils logo-icon"></i>
                        <h3 class="mt-2 fw-bold">Cilung Malfi</h3>
                        <p class="text-muted">Silakan login</p>
                    </div>
                    <?php if($error) echo "<div class='alert alert-danger'>$error</div>"; ?>
                    <form method="POST">
                        <div class="mb-3 input-group">
                            <span class="input-group-text bg-white border-end-0"><i class="fas fa-user"></i></span>
                            <input type="text" name="username" class="form-control border-start-0" placeholder="Username" required>
                        </div>
                        <div class="mb-4 input-group">
                            <span class="input-group-text bg-white border-end-0"><i class="fas fa-lock"></i></span>
                            <input type="password" name="password" class="form-control border-start-0" placeholder="Password" required>
                        </div>
                        <button type="submit" class="btn btn-login text-white w-100">Login</button>
                    </form>
                    <hr>
                    <div class="text-center small">Demo: admin/admin123 | kasir/kasir123</div>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>