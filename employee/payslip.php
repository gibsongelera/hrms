<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

require_once '../includes/db_connection.php';
$employee_id = $_SESSION['employee_id'];

// Get latest payroll record
$stmt = $pdo->prepare("SELECT p.*, e.first_name, e.last_name, e.job_role, e.department 
                       FROM payroll p 
                       JOIN employees e ON p.employee_id = e.id 
                       WHERE p.employee_id = ? 
                       ORDER BY p.month_year DESC LIMIT 1");
$stmt->execute([$employee_id]);
$payroll = $stmt->fetch();

if (!$payroll) {
    // For demo purposes, if no payroll exists, let's show a "Not Found" message 
    // or just assume we'll seed one. I'll seed one in seed.php later if needed.
}

include '../includes/header.php';
?>
<div class="dashboard-container">
    <?php include '../includes/sidebar.php'; ?>

    <main class="main-content">
        <header style="margin-bottom: 2.5rem;">
            <h2 style="font-size: 1.75rem; font-weight: 800;">My Payslip</h2>
            <p style="color: var(--text-muted); font-size: 0.95rem;">Access and manage your monthly earnings documents.
            </p>
        </header>

        <?php if ($payroll): ?>
            <!-- Notification Message Box -->
            <div class="message-card-wrapper" style="max-width: 600px; margin: 4rem auto;">
                <div class="message-card"
                    style="background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(20px); padding: 3rem; border-radius: 24px; border: 1px solid rgba(79, 70, 229, 0.3); text-align: center; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5); animation: slideUp 0.6s ease-out;">
                    <div
                        style="width: 70px; height: 70px; background: linear-gradient(135deg, var(--primary), var(--accent)); border-radius: 20px; display: flex; align-items: center; justify-content: center; margin: 0 auto 2rem; box-shadow: 0 10px 20px var(--primary-glow);">
                        <i data-lucide="mail" style="width: 35px; height: 35px; color: white;"></i>
                    </div>
                    <h3 style="font-size: 1.75rem; font-weight: 800; color: white; margin-bottom: 1rem;">Monthly Payroll
                        Ready</h3>
                    <p style="color: var(--text-muted); font-size: 1.1rem; margin-bottom: 2.5rem; line-height: 1.6;">
                        Hello, <strong><?= $payroll['first_name'] ?></strong>. Your official payslip for <span
                            style="color: var(--accent); font-weight: 700;"><?= date('F Y', strtotime($payroll['month_year'])) ?></span>
                        has been processed and is ready for viewing.
                    </p>
                    <button class="btn btn-primary" onclick="openPayslipModal()"
                        style="padding: 1rem 2.5rem; font-size: 1rem; border-radius: 100px; display: inline-flex; align-items: center; gap: 0.75rem; text-decoration: none; cursor: pointer;">
                        <i data-lucide="file-text"></i> View Payslip Document
                    </button>
                    <p style="margin-top: 2rem; font-size: 0.85rem; color: #475569;">
                        <i data-lucide="clock" style="width: 14px; vertical-align: middle;"></i> Issued on
                        <?= date('M d, Y', strtotime($payroll['payment_date'])) ?>
                    </p>
                </div>
            </div>

            <!-- Payslip Modal -->
            <div id="payslipModal" class="modal-backdrop"
                style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.85); backdrop-filter: blur(8px); z-index: 9999; overflow-y: auto; padding: 2rem 1rem;">
                <div class="modal-content"
                    style="max-width: 760px; margin: 0 auto; animation: modalZoom 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);">

                    <!-- Modal Controls -->
                    <div style="display: flex; justify-content: flex-end; gap: 1rem; margin-bottom: 1.5rem;">
                        <button onclick="window.print()"
                            style="background: #1e293b; color: white; border: none; padding: 0.75rem 1.75rem; border-radius: 12px; display: flex; align-items: center; gap: 0.6rem; cursor: pointer; font-weight: 600; font-size: 0.95rem; box-shadow: 0 4px 12px rgba(0,0,0,0.3); transition: transform 0.2s;">
                            <i data-lucide="printer" style="width: 20px;"></i> Save as PDF
                        </button>
                        <button class="btn-danger" onclick="closePayslipModal()"
                            style="width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; padding: 0; cursor: pointer; border: none; background: #dc2626; color: white; box-shadow: 0 4px 12px rgba(220, 38, 38, 0.3);">
                            <i data-lucide="x" style="width: 24px;"></i>
                        </button>
                    </div>

                    <!-- The Payslip Document -->
                    <div class="payslip-card"
                        style="background: #ffffff; padding: 2.5rem; border-radius: 16px; color: #1e293b; position: relative; overflow: hidden; box-shadow: 0 30px 60px rgba(0,0,0,0.5);">

                        <!-- Top Accent Bar -->
                        <div
                            style="position: absolute; top: 0; left: 0; width: 100%; height: 6px; background: linear-gradient(90deg, var(--primary), var(--accent));">
                        </div>

                        <!-- Header -->
                        <div
                            style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 2rem; border-bottom: 1px solid #e2e8f0; padding-bottom: 1.5rem;">
                            <div>
                                <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.25rem;">
                                    <div
                                        style="width: 32px; height: 32px; background: #4f46e5; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: white;">
                                        <i data-lucide="shield-check" style="width: 18px; height: 18px;"></i>
                                    </div>
                                    <h1 style="font-size: 1.4rem; font-weight: 800; color: #0f172a; margin: 0;">HRMS
                                    </h1>
                                </div>
                                <p style="color: #64748b; font-size: 0.8rem; margin: 0;">Enterprise Building, Tech City,
                                    10001</p>
                            </div>
                            <div style="text-align: right;">
                                <div
                                    style="background: #f8fafc; padding: 0.4rem 0.8rem; border-radius: 6px; border: 1px solid #e2e8f0;">
                                    <span
                                        style="font-size: 0.65rem; color: #64748b; text-transform: uppercase; font-weight: 700; display: block; letter-spacing: 0.05em;">Payroll
                                        Period</span>
                                    <span
                                        style="font-size: 0.95rem; font-weight: 700; color: #0f172a;"><?= date('F Y', strtotime($payroll['month_year'])) ?></span>
                                </div>
                            </div>
                        </div>

                        <!-- Info Grid -->
                        <div
                            style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-bottom: 2rem; background: #f8fafc; padding: 1.5rem; border-radius: 12px; border: 1px solid #e2e8f0;">
                            <div style="font-size: 0.85rem;">
                                <h4
                                    style="color: #64748b; text-transform: uppercase; font-size: 0.7rem; letter-spacing: 0.05em; margin-bottom: 0.75rem; font-weight: 700;">
                                    Employee Information</h4>
                                <p style="margin: 0.4rem 0; color: #334155;"><strong>Name:</strong> <span
                                        style="color: #0f172a;"><?= $payroll['first_name'] ?>
                                        <?= $payroll['last_name'] ?></span></p>
                                <p style="margin: 0.4rem 0; color: #334155;"><strong>Position:</strong> <span
                                        style="color: #0f172a;"><?= $payroll['job_role'] ?></span></p>
                                <p style="margin: 0.4rem 0; color: #334155;"><strong>Department:</strong> <span
                                        style="color: #0f172a;"><?= $payroll['department'] ?></span></p>
                            </div>
                            <div style="font-size: 0.85rem;">
                                <h4
                                    style="color: #64748b; text-transform: uppercase; font-size: 0.7rem; letter-spacing: 0.05em; margin-bottom: 0.75rem; font-weight: 700;">
                                    Payment Details</h4>
                                <p style="margin: 0.4rem 0; color: #334155;"><strong>Issue Date:</strong> <span
                                        style="color: #0f172a;"><?= date('M d, Y', strtotime($payroll['payment_date'])) ?></span>
                                </p>
                                <p style="margin: 0.4rem 0; color: #334155;"><strong>Method:</strong> <span
                                        style="color: #0f172a;">Bank Transfer</span></p>
                                <p style="margin: 0.4rem 0; color: #334155;"><strong>Status:</strong> <span
                                        style="color: #10b981; font-weight: 700;">Processed</span></p>
                            </div>
                        </div>

                        <!-- Table -->
                        <table
                            style="width: 100%; border-collapse: separate; border-spacing: 0; margin-bottom: 2rem; font-size: 0.85rem;">
                            <thead>
                                <tr style="background: #0f172a; color: white;">
                                    <th style="padding: 1rem; text-align: left; border-radius: 8px 0 0 8px;">Description
                                    </th>
                                    <th style="padding: 1rem; text-align: right;">Earnings</th>
                                    <th style="padding: 1rem; text-align: right; border-radius: 0 8px 8px 0;">Deductions
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td
                                        style="padding: 1rem; border-bottom: 1px solid #e2e8f0; font-weight: 500; color: #1e293b;">
                                        Basic Salary</td>
                                    <td
                                        style="padding: 1rem; border-bottom: 1px solid #e2e8f0; text-align: right; color: #10b981; font-weight: 600;">
                                        ₱<?= number_format($payroll['base_salary'], 2) ?></td>
                                    <td
                                        style="padding: 1rem; border-bottom: 1px solid #e2e8f0; text-align: right; color: #94a3b8;">
                                        -</td>
                                </tr>
                                <tr>
                                    <td
                                        style="padding: 1rem; border-bottom: 1px solid #e2e8f0; font-weight: 500; color: #1e293b;">
                                        Statutory Deductions</td>
                                    <td
                                        style="padding: 1rem; border-bottom: 1px solid #e2e8f0; text-align: right; color: #94a3b8;">
                                        -</td>
                                    <td
                                        style="padding: 1rem; border-bottom: 1px solid #e2e8f0; text-align: right; color: #ef4444; font-weight: 600;">
                                        ₱<?= number_format($payroll['deductions'], 2) ?></td>
                                </tr>
                            </tbody>
                        </table>

                        <!-- Total Row -->
                        <div style="display: flex; justify-content: flex-end; margin-top: 1rem;">
                            <div
                                style="width: 260px; padding: 1.5rem; background: #0f172a; color: white; border-radius: 12px;">
                                <div style="display: flex; justify-content: space-between; align-items: baseline;">
                                    <span
                                        style="font-size: 0.8rem; font-weight: 700; text-transform: uppercase; color: #94a3b8;">Net
                                        Pay</span>
                                    <span class="net-pay-amount"
                                        style="font-size: 1.5rem; font-weight: 800; color: #10b981;">₱<?= number_format($payroll['net_pay'], 2) ?></span>
                                </div>
                            </div>
                        </div>

                        <!-- Footer -->
                        <div
                            style="margin-top: 2rem; padding-top: 1.5rem; border-top: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center;">
                            <p style="font-size: 0.7rem; color: #94a3b8; margin: 0;">Verified Digital Document • HRMS
                                Secure</p>
                            <div
                                style="opacity: 0.5; font-size: 0.65rem; color: #64748b; font-weight: 700; border: 1px dashed #cbd5e1; padding: 0.25rem 0.5rem; border-radius: 4px;">
                                SYSTEM SEAL: VERIFIED</div>
                        </div>
                    </div>
                </div>
            </div>

            <script>
                function openPayslipModal() {
                    document.getElementById('payslipModal').style.display = 'block';
                    document.body.style.overflow = 'hidden';
                }
                function closePayslipModal() {
                    document.getElementById('payslipModal').style.display = 'none';
                    document.body.style.overflow = 'auto';
                }
                // Close on click outside
                window.onclick = function (event) {
                    let modal = document.getElementById('payslipModal');
                    if (event.target == modal) {
                        closePayslipModal();
                    }
                }
            </script>
        <?php else: ?>
            <div
                style="text-align: center; padding: 8rem 2rem; background: rgba(255,255,255,0.03); border-radius: 20px; border: 1px dashed rgba(255,255,255,0.1);">
                <div
                    style="width: 80px; height: 80px; background: rgba(79, 70, 229, 0.1); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.5rem;">
                    <i data-lucide="file-warning" style="width: 40px; height: 40px; color: var(--primary);"></i>
                </div>
                <h3 style="font-size: 1.5rem; font-weight: 700;">No Recent Payslips Found</h3>
                <p style="color: var(--text-muted); max-width: 400px; margin: 0.5rem auto 2rem;">Payroll for the current
                    month has not been finalized yet. Please check back later or contact your manager.</p>
                <a href="dashboard.php" class="btn btn-secondary"
                    style="text-decoration: none; padding: 0.75rem 2rem;">Return to Dashboard</a>
            </div>
        <?php endif; ?>
    </main>
</div>

<style>
    @keyframes slideUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @keyframes modalZoom {
        from {
            opacity: 0;
            transform: scale(0.9) translateY(20px);
        }

        to {
            opacity: 1;
            transform: scale(1) translateY(0);
        }
    }

    @media print {
        @page {
            margin: 1cm;
            size: a4 portrait;
        }

        /* 1. Reset Body and Container strictly for Print */
        html,
        body {
            background: #ffffff !important;
            color: #000000 !important;
            overflow: visible !important;
            height: auto !important;
            margin: 0 !important;
            padding: 0 !important;
            width: 100% !important;
        }

        /* 2. Hide ALL except the container chain to the payslip */
        body>*:not(.dashboard-container) {
            display: none !important;
        }

        .dashboard-container {
            display: block !important;
            padding: 0 !important;
            margin: 0 !important;
        }

        .dashboard-container>*:not(.main-content) {
            display: none !important;
        }

        .main-content {
            display: block !important;
            padding: 0 !important;
            margin: 0 !important;
        }

        .main-content>*:not(#payslipModal) {
            display: none !important;
        }

        /* 3. Normalize the Modal and Card */
        #payslipModal {
            display: block !important;
            position: relative !important;
            background: none !important;
            backdrop-filter: none !important;
            padding: 0 !important;
            margin: 0 !important;
            visibility: visible !important;
            opacity: 1 !important;
        }

        .modal-content {
            display: block !important;
            margin: 0 !important;
            max-width: 100% !important;
            width: 100% !important;
            box-shadow: none !important;
            transform: none !important;
            animation: none !important;
        }

        .modal-content>div:first-child {
            display: none !important;
            /* Hide modal buttons (Save, X) */
        }

        .payslip-card {
            display: block !important;
            border: 1px solid #e2e8f0 !important;
            padding: 2.5rem !important;
            margin: 0 auto !important;
            border-radius: 16px !important;
            background: #ffffff !important;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        /* 4. Formatting Fixes */
        * {
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        .payslip-card tr[style*="background: #0f172a"],
        .payslip-card div[style*="background: #0f172a"] {
            background-color: #0f172a !important;
            color: #ffffff !important;
        }

        .payslip-card div[style*="background: #4f46e5"] {
            background-color: #4f46e5 !important;
            color: #ffffff !important;
        }

        .net-pay-amount {
            color: #10b981 !important;
        }

        td[style*="color: #10b981"] {
            color: #10b981 !important;
        }

        td[style*="color: #ef4444"] {
            color: #ef4444 !important;
        }
    }
</style>

<?php include '../includes/footer.php'; ?>