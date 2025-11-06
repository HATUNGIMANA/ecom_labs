<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Home</title>
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
	<style>
		.menu-tray {
			position: fixed;
			top: 16px;
			right: 16px;
			background: rgba(255,255,255,0.95);
			border: 1px solid #e6e6e6;
			border-radius: 8px;
			padding: 6px 10px;
			box-shadow: 0 4px 10px rgba(0,0,0,0.06);
			z-index: 1000;
		}
		.menu-tray a { margin-left: 8px; }
		.welcome-user {
			color: #D19C97;
			font-weight: bold;
		}
	</style>
</head>
<body>

	<div class="menu-tray">
		<span class="me-2">Menu:</span>
		<?php if (isset($_SESSION['customer_id'])): ?>
			<span class="welcome-user me-2">Welcome, <?php echo htmlspecialchars($_SESSION['customer_name']); ?>!</span>
			<?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] == 1): ?>
				<a href="admin/category.php" class="btn btn-sm btn-outline-primary">Category</a>
			<?php endif; ?>
			<a href="login/logout.php" class="btn btn-sm btn-outline-danger">Logout</a>
		<?php else: ?>
			<a href="login/register.php" class="btn btn-sm btn-outline-primary">Register</a>
			<a href="login/login.php" class="btn btn-sm btn-outline-secondary">Login</a>
		<?php endif; ?>
	</div>

	<div class="container" style="padding-top:120px;">
		<div class="text-center">
			<h1>Welcome</h1>
			<?php if (isset($_SESSION['customer_id'])): ?>
				<p class="text-muted">Welcome back, <?php echo htmlspecialchars($_SESSION['customer_name']); ?>!</p>
				<p class="text-muted">You are logged in as <?php echo ($_SESSION['user_role'] == 1) ? 'Restaurant Owner' : 'Customer'; ?>.</p>
			<?php else: ?>
				<p class="text-muted">Use the menu in the top-right to Register or Login.</p>
			<?php endif; ?>
		</div>
	</div>

	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
