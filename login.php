<?php
// Prevent caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

require_once 'includes/db_connection.php';
session_start();

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT u.*, r.role_name FROM users u JOIN roles r ON u.role_id = r.id WHERE u.email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role_name'];
        $_SESSION['email'] = $user['email'];

        $stmt_emp = $pdo->prepare("SELECT id FROM employees WHERE user_id = ?");
        $stmt_emp->execute([$user['id']]);
        $emp = $stmt_emp->fetch();
        $_SESSION['employee_id'] = $emp ? $emp['id'] : null;

        if ($user['role_name'] == 'Admin') {
            header("Location: admin/dashboard.php");
        } else {
            header("Location: employee/dashboard.php");
        }
        exit;
    } else {
        $_SESSION['login_error'] = "Invalid email or password";
        header("Location: login.php");
        exit;
    }
}
$error = isset($_SESSION['login_error']) ? $_SESSION['login_error'] : '';
unset($_SESSION['login_error']);
session_write_close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - HRMS</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Outfit', sans-serif;
            background: #0f172a;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }

        /* Abstract Geometric Background - Teamwork Theme */
        .bg-shapes {
            position: fixed;
            inset: 0;
            z-index: 0;
            overflow: hidden;
        }

        .bg-shapes::before {
            content: '';
            position: absolute;
            inset: 0;
            background: radial-gradient(circle at 50% 50%, rgba(30, 41, 59, 1) 0%, rgba(15, 23, 42, 1) 100%);
        }

        /* Floating Structured Shapes */
        .shape {
            position: absolute;
            opacity: 0.3;
            filter: blur(2px);
            /* Sharper, more professional */
            animation: float 20s infinite ease-in-out alternate;
            border: 2px solid rgba(255, 255, 255, 0.1);
        }

        .shape-1 {
            top: 5%;
            left: 5%;
            width: 300px;
            height: 300px;
            background: linear-gradient(135deg, rgba(79, 70, 229, 0.2), transparent);
            clip-path: polygon(50% 0%, 100% 38%, 82% 100%, 18% 100%, 0% 38%);
            animation-duration: 25s;
        }

        .shape-2 {
            bottom: 5%;
            right: 5%;
            width: 250px;
            height: 250px;
            background: linear-gradient(135deg, rgba(6, 182, 212, 0.2), transparent);
            clip-path: circle(50% at 50% 50%);
            animation-duration: 30s;
            animation-direction: alternate-reverse;
        }

        .shape-3 {
            top: 50%;
            left: 80%;
            width: 150px;
            height: 150px;
            background: linear-gradient(135deg, rgba(139, 92, 246, 0.1), transparent);
            clip-path: polygon(25% 0%, 100% 0%, 75% 100%, 0% 100%);
            animation-duration: 20s;
        }

        /* Network Connections Pattern (Teamwork Theme) */
        .network-grid {
            position: fixed;
            inset: 0;
            background-image:
                radial-gradient(circle at 2px 2px, rgba(255, 255, 255, 0.05) 1px, transparent 0);
            background-size: 40px 40px;
            z-index: 1;
        }

        .network-grid::after {
            content: '';
            position: absolute;
            inset: 0;
            background:
                url("data:image/svg+xml,%3Csvg width='100' height='100' viewBox='0 0 100 100' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M20 20 L50 50 M50 50 L80 20 M50 50 L50 80' stroke='rgba(255,255,255,0.02)' stroke-width='1' fill='none'/%3E%3Ccircle cx='20' cy='20' r='1' fill='rgba(255,255,255,0.05)'/%3E%3Ccircle cx='50' cy='50' r='1' fill='rgba(255,255,255,0.05)'/%3E%3Ccircle cx='80' cy='20' r='1' fill='rgba(255,255,255,0.05)'/%3E%3Ccircle cx='50' cy='80' r='1' fill='rgba(255,255,255,0.05)'/%3E%3C/svg%3E");
            z-index: 2;
        }

        @keyframes float {
            0% {
                transform: translate(0, 0) rotate(0deg);
            }

            100% {
                transform: translate(30px, 50px) rotate(10deg);
            }
        }

        /* Login Container */
        .login-wrapper {
            position: relative;
            z-index: 10;
            width: 100%;
            max-width: 540px;
            padding: 2rem;
            animation: fadeIn 0.8s ease-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .login-card {
            background: linear-gradient(145deg, rgba(30, 41, 59, 0.98), rgba(15, 23, 42, 0.98));
            backdrop-filter: blur(10px);
            border-radius: 24px;
            padding: 3.5rem 3rem;
            box-shadow:
                0 25px 50px -12px rgba(0, 0, 0, 0.5),
                0 0 0 1px rgba(255, 255, 255, 0.08) inset;
            border: 1px solid rgba(79, 70, 229, 0.2);
            position: relative;
            overflow: hidden;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .login-card:hover {
            box-shadow:
                0 30px 60px -12px rgba(0, 0, 0, 0.6),
                0 0 0 1px rgba(79, 70, 229, 0.3) inset,
                0 0 40px rgba(79, 70, 229, 0.1);
            transform: translateY(-5px);
        }

        /* Top Colors Bar */
        .login-card::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #4f46e5, #06b6d4);
        }

        /* Header */
        .login-header {
            text-align: center;
            margin-bottom: 2.5rem;
        }

        .logo-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #4f46e5, #06b6d4);
            border-radius: 20px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1.5rem;
            box-shadow: 0 10px 25px rgba(79, 70, 229, 0.3);
            position: relative;
        }

        .logo-icon svg {
            color: white;
            filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.1));
        }

        .login-title {
            font-size: 2.5rem;
            font-weight: 800;
            color: white;
            margin-bottom: 0.5rem;
            letter-spacing: -0.02em;
        }

        .login-subtitle {
            color: #94a3b8;
            font-size: 1rem;
            font-weight: 500;
        }

        /* Form */
        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            color: #e2e8f0;
            font-weight: 600;
            font-size: 0.9rem;
            margin-bottom: 0.75rem;
            transition: all 0.3s ease;
        }

        .form-group:focus-within .form-label {
            color: #818cf8;
            transform: translateX(4px);
        }

        .input-wrapper {
            position: relative;
        }

        .input-icon {
            position: absolute;
            left: 1.25rem;
            top: 50%;
            transform: translateY(-50%);
            color: #64748b;
            transition: color 0.3s ease;
            pointer-events: none;
        }

        .form-input {
            width: 100%;
            padding: 1.125rem 1.125rem 1.125rem 3.5rem;
            background: rgba(15, 23, 42, 0.6);
            border: 2px solid rgba(51, 65, 85, 0.5);
            border-radius: 12px;
            color: white;
            font-size: 1rem;
            font-family: inherit;
            transition: all 0.3s ease;
        }

        .form-input:focus {
            outline: none;
            background: rgba(15, 23, 42, 0.8);
            border-color: #4f46e5;
            box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.1);
        }

        .form-input:focus+.input-icon {
            color: #4f46e5;
        }

        .form-input::placeholder {
            color: #64748b;
        }

        /* Button */
        .login-btn {
            width: 100%;
            padding: 1.125rem;
            background: linear-gradient(135deg, #4f46e5, #4338ca);
            border: none;
            border-radius: 12px;
            color: white;
            font-size: 1.05rem;
            font-weight: 600;
            cursor: pointer;
            margin-top: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }

        .login-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 10px 15px -3px rgba(79, 70, 229, 0.3);
            background: linear-gradient(135deg, #4338ca, #3730a3);
        }

        .login-btn:active {
            transform: translateY(0);
        }

        /* Error */
        .error-box {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.2);
            color: #fca5a5;
            padding: 1rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 0.9rem;
        }

        /* Footer */
        .login-footer {
            text-align: center;
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid rgba(255, 255, 255, 0.05);
            color: #64748b;
            font-size: 0.875rem;
        }
    </style>
</head>

<body>
    <!-- Geometric Background -->
    <div class="bg-shapes">
        <div class="shape shape-1"></div>
        <div class="shape shape-2"></div>
        <div class="shape shape-3"></div>
    </div>

    <!-- Subtle Network Grid Overlay -->
    <div class="network-grid"></div>

    <div class="login-wrapper">
        <div class="login-card">
            <div class="login-header">
                <div class="logo-icon">
                    <!-- HR People Icon -->
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                        <circle cx="9" cy="7" r="4"></circle>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                    </svg>
                </div>
                <h1 class="login-title">HRMS</h1>
                <p class="login-subtitle">Enterprise HR Management System</p>
            </div>

            <?php if ($error): ?>
                <div class="error-box">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="8" x2="12" y2="12"></line>
                        <line x1="12" y1="16" x2="12.01" y2="16"></line>
                    </svg>
                    <span><?= htmlspecialchars($error) ?></span>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label class="form-label">Email Address</label>
                    <div class="input-wrapper">
                        <input type="email" name="email" class="form-input" placeholder="you@company.com" required>
                        <svg class="input-icon" width="20" height="20" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2">
                            <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z">
                            </path>
                            <polyline points="22,6 12,13 2,6"></polyline>
                        </svg>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Password</label>
                    <div class="input-wrapper">
                        <input type="password" name="password" class="form-input" placeholder="••••••••" required>
                        <svg class="input-icon" width="20" height="20" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2">
                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                            <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                        </svg>
                    </div>
                </div>

                <button type="submit" class="login-btn">
                    Sign In
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="2.5">
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                        <polyline points="12 5 19 12 12 19"></polyline>
                    </svg>
                </button>
            </form>

            <div class="login-footer">
                🔒 Secure enterprise authentication
            </div>
        </div>
    </div>
</body>

</html>