<?php
	require_once('fn.php');

	if ($_POST && $_POST['page'])
		switch ($_POST['page']) {
			case "signup":
				if (is_array($return = new_user($_POST['login'], $_POST['passwd'])))
					$_SESSION['user'] = $return;
				else
					$ERROR_MSG = $return;
				unset($_POST['page']);
				break;
			case "login":
				if (is_array($return = connect_user($_POST['login'], $_POST['passwd'])))
					$_SESSION['user'] = $return;
				else
					$ERROR_MSG = $return;
				unset($_POST['page']);
				break;
			case "logout":
				session_destroy();
				session_start();
				header('Location: index.php');
				break;
			case "products":
				if ($_POST['id'] !== null)
					if (($product = get_product_by_id(intval($_POST['id']))) === null)
					{
						$ERROR_MSG = "Product not found";
						unset($_POST);
					}
				break;
			case "orders":
				if ($_POST['id'] !== null)
					$order = get_orders($_POST['id']);
				break;
			case "modif_del":
				if ($_POST['id'] !== null)
					$product = get_product_by_id(intval($_POST['id']));
				else if ($_POST['login'] !== null)
					$user = get_user_by_login($_POST['login']);
				else if ($_POST['category'] !== null)
					$category = get_category_by_name($_POST['category']);
				break;
			case "account":
				if (empty($_SESSION['user']))
					header('Location: index.php');
				break;
			case "account_modify":
				if (is_array($return = modify_user($_SESSION['user']['login'], $_POST['address'], $_POST['passwd'], $_POST['newpasswd']))) {
					$_SESSION['user'] = $return;
					header('Location: index.php');
				} else
					$ERROR_MSG = $return;
				unset($_POST['page']);
				break;
			case "account_delete":
				if (($return = delete_user($_SESSION['user']['login'], $_POST['passwd'])) === true) {
					session_destroy();
					session_start();
					header('Location: index.php');
				} else
					$ERROR_MSG = $return;
				unset($_POST['page']);
				break;
			case "product_add":
				if (!is_array($return = new_product($_POST['name'], $_POST['categories'], $_POST['description'], floatval($_POST['price']), $_POST['colorable'] === "on")))
					$ERROR_MSG = $return;
				unset($_POST);
				$_POST['page'] = 'product_catg_user_add';
				break;
			case "product_delete":
				if (($return = delete_product(intval($_POST['product_id']))) !== true)
					$ERROR_MSG = $return;
				$_POST['page'] = 'modif_del';
				break;
			case "product_modified":
				D($_POST['categories']);
				if (!is_array($return = modify_product(intval($_POST['product_id']), $_POST['name'], $_POST['categories'], $_POST['description'], floatval($_POST['price']), $_POST['colorable'] === "on")))
					$ERROR_MSG = $return;
				unset($_POST);
				$_POST['page'] = 'modif_del';
				break;
			case "category_add":
				if (($return = new_category($_POST['name'])) !== $_POST['name'])
					$ERROR_MSG = $return;
				unset($_POST);
				$_POST['page'] = 'product_catg_user_add';
				break;
			case "category_delete":
				if (($return = delete_category($_POST['name'])) !== true)
					$ERROR_MSG = $return;
				unset($_POST);
				$_POST['page'] = 'modif_del';
				break;
			case "user_add":
				if (!is_array($return = new_user($_POST['login'], $_POST['password'], false)))
					$ERROR_MSG = $return;
				unset($_POST);
				$_POST['page'] = 'product_catg_user_add';
				break;
			case "user_delete":
				if (($return = delete_user($_POST['login'], $_POST['password'], true)) !== true)
					$ERROR_MSG = $return;
				unset($_POST);
				$_POST['page'] = 'modif_del';
				break;
			case "user_modified":
				if (!is_array($return = modify_user($_POST['login'], $_POST['address'], null, $_POST['newpasswd'], true)))
					$ERROR_MSG = $return;
				unset($_POST);
				$_POST['page'] = 'modif_del';
				break;
			case "cart":
				if ($_POST['add'] == '1') {
					$product = get_product_by_id(intval($_POST['id']));
					unset($_POST['add']);
					$_POST['page'] = 'products';
					$_SESSION['cart'] = addto_cart($product);
				}
				if ($_POST['del'] == '1') {
					$product = get_product_by_id(intval($_POST['id']));
					unset($_POST['del']);
					$_SESSION['cart'] = delfrom_cart($product);
				}
				if ($_POST['add_order'] == '1') {
					if (new_order($_SESSION['cart'], $_SESSION['user']) !== false) {
						unset($_POST['add_order'], $_SESSION['cart']);
						header('Location: index.php');
					} else
						$ERROR_MSG = "Error occured while saving your cart";
				}
				if (empty($_SESSION['cart'])) {
					$ERROR_MSG = "Your cart is empty";
					unset($_POST['page']);
				}
				break;
			case "manage":
				if ($_SESSION['user']['admin'] === false)
					header('Location: index.php');
			case "orders":
				if ($_SESSION['user']['admin'] === false)
					header('Location: index.php');
		}
	
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link rel="stylesheet" href="style.css">
    <title>TB - 42Store</title>
</head>
<body>
	<div id="overlay" class="overlay none" onclick="del(this);"></div>
	<header>
		<h2><a href="index.php">42Store</a></h2>
		<nav>
			<li><a href="index.php">Home</a></li>
			<li><a href="?page=products" class="post">Shop</a></li>
			<?php if ($_SESSION['cart']):?>
				<li><a href="?page=cart" class="post">My cart (<?=count($_SESSION['cart'])-2?>)</a></li>
			<?php endif;?>
			<?php if ($_SESSION['user']):?>
				<li><a href="?page=account" class="post">Account</a></li>
				<?php if ($_SESSION['user']['admin'] === true):?>
					<li><a href="?page=manage" class="post">Manage</a></li>
				<?php endif ?>
				<li><a href="?page=logout" class="post">Logout</a></li>
			<?php else:?>
				<li><a href="?login" onclick="pop(this.href.split('?')[1]); return false;">Login</a></li>
			<?php endif;?>
		</nav>
	</header>

	<?php if ($ERROR_MSG):?>
		<section class="error_msg" onclick="del(this);">
			<h4 class="title"><?= $ERROR_MSG?></h4>
		</section>
	<?php endif;?>

	<?php if (empty($_SESSION['user'])):?>
		<section class="login" id="login">
			<h3 class="title">Login / Sign Up</h3>
			<form action="" method="post" id="form_login">
				<ul class="grid-item">
					<li>Username:<input type="text" name="login"></li>
					<li>Password:<input type="password" name="passwd"></li>
					<li><div>
						<a href="?form_login=login" class="submit btn">Login</a>
						<a href="?form_login=signup" class="submit btn right">Signup</a>
					</div></li>
					<input type="hidden" name="page">
				</ul>
			</form>
		</section>
	<?php endif;?>

	<?php if ($_POST['page']):?>
		<section class="splashscreen navbar">
			<div class="background-image" style="background-image: url(img/splashscreen.jpg);"> </div>
		</section>
	<?php else:?>
		<section class="splashscreen">
			<div class="background-image" style="background-image: url(img/splashscreen.jpg);"></div>
			<h1>Welcome to 42Store</h1>
			<h3>A shopping website by tbailleu and aledru.</h3>
			<a href="?page=products" class="post btn">Start Shopping</a>
		</section>
		<section class="recently-added">
			<h3 class="title">Recently added</h3>
			<p>Check out these new items added to the store, maybe you will find your wish</p>
			<hr>
			
			<ul class="grid-item">
				<?php foreach (get_products(4) as $key => $product):?>
					<a href="?page=products&id=<?=$product['id']?>" class="post" style="background-image: url(img/products/<?=$product['id']?>.png);">
						<p class="product"><?=$product['name']?> <span><?=$product['price']?> $</span> <span><?=$product['categories'][0]?></span></p>
						<p class="description"><?=$product['description']?></p>
					</a>
				<?php endforeach;?>
			</ul>
		</section>

		<section class="categories">
			<h3 class="title">Categories</h3>
			<p>You can browse items by categories, check out those one</p>
			<hr>
			<ul class="grid-item">
				<?php foreach (get_categories() as $category):?>
				<li><a href="?page=products&category=<?=$category?>" class="post"><h4>[ <?=$category?> ]</h4></a></li>
				<?php endforeach;?>
			</ul>
		</section>
	<?php endif;?>
	
	<?php if ($_POST['page'] == 'products'):?>
		<section class="products">
			<?php if ($_POST['id'] !== null):?>
				<h3 class="title"><a href="?page=products&id=<?=$_POST['id']?>" class="post"><?=$product['name']?></a></h3>
				<p>
					<?php foreach ($product['categories'] as $id => $category):?>
						<span class="category"><?=$category?></span>
					<?php endforeach;?>
					<br><?=$product['description']?>
				</p>
				<hr>
				<img src="img/products/<?=$product['id']?>.png" alt="<?=$product['name']?>">
				<a href="?page=cart&add=1&id=<?=$product['id']?>" class="post btn">Add to cart</a>
			<?php elseif ($_POST['category']  !== null):?>
				<h3 class="title"><a href="?page=products&category=<?=$_POST['category']?>" class="post"><?=urldecode($_POST['category'])?></a></h3>
				<p>Check out these new items added to the store, maybe you will find your wish</p>
				<hr>
				<ul class="grid-item">
					<?php foreach (get_products_by_category(urldecode($_POST['category'])) as $key => $product):?>
						<a href="?page=products&id=<?=$product['id']?>" class="post" style="background-image: url(img/products/<?=$product['id']?>.png);">
							<p class="product"><?=$product['name']?> <span><?=$product['price']?> $</span> <span><?=$product['categories'][0]?></span></p>
							<p class="description"><?=$product['description']?></p>
						</a>
					<?php endforeach;?>
				</ul>
						<input type="hidden" name="page">
			<?php else:?>
				<h3 class="title"><a href="?page=products" class="post">Products</a></h3>
				<p>Check out these new items added to the store, maybe you will find your wish</p>
				<hr>
				<ul class="grid-item">
					<?php foreach (get_products() as $key => $product):?>
						<a href="?page=products&id=<?=$product['id']?>" class="post" style="background-image: url(img/products/<?=$product['id']?>.png);">
							<p class="product"><?=$product['name']?> <span><?=$product['price']?> $</span> <span><?=$product['categories'][0]?></span></p>
							<p class="description"><?=$product['description']?></p>
						</a>
					<?php endforeach;?>
				</ul>
			<?php endif;?>
		</section>
	<?php endif;?>
	
	<?php if ($_POST['page'] == 'account'):?>
		<section class="account">
			<h3 class="title"><a href="?page=account" class="post">Account</a></h3>
			<?php if ($_SESSION['user']['address'] === false):?>
				<p>Add your delivery address for shipping</p>
			<?php else:?>
				<p>Modify your personnal infomation</p>
			<?php endif;?>
			<hr>
			<form action="" method="post" id="form_account">
				<ul class="grid-item">
					<li>Shipping address:<input type="text" name="address" value="<?=$_SESSION['user']['address']?>"></li>
					<li>New password:<input type="password" name="newpasswd" placeholder="Leave empty to not modify"></li>
					<li>Confirm password:<input type="password" name="passwd"></li>
					<li><div>
						<a href="?form_account=account_modify" class="submit btn">Save</a>
						<?php if ($_SESSION['user']['admin'] === false):?>
							<a href="?form_account=account_delete" class="submit btn right delete">Delete</a>
						<?php endif;?>
					</div></li>
					<input type="hidden" name="page">
				</ul>
			</form>
		</section>
	<?php endif;?>
	
	<?php if ($_POST['page'] == 'cart'):?>
		<section class="cart">
			<h3 class="title"><a href="?page=cart" class="post">Cart</a></h3>
			<p>
				These <?=($_SESSION['cart']['quantity']>1?'are ':'is ').$_SESSION['cart']['quantity']?>
				<?=($_SESSION['cart']['quantity']>1?' items ':' item ')?>in your shopping cart,
				for a total of <?=$_SESSION['cart']['price']?> $
			</p>
			<hr>
			<ul class="grid-item">
				<?php foreach ($_SESSION['cart'] as $key => $product):?>
					<?php if (is_string($key)) continue;?>
					<a href="?page=products&id=<?=$product['id']?>" class="post">
						<li><?=$product['name']?> <span><?=$product['price']?> $</span></li>
					</a>
					<a href="?page=cart&del=1&id=<?=$product['id']?>" class="post delete">
						<li>Remove <span> X <?=$product['quantity']?></span></li>
					</a>
				<?php endforeach;?>
			</ul>
				<?php if ($_SESSION['user']):?>
					<a href="?page=cart&add_order=1" class="post btn">Purchase</a>
				<?php else:?>
					<a href="?login" onclick="pop(this.href.split('?')[1]); return false;" class="btn">You need to be logged in to purchase</a>
				<?php endif;?>
		</section>
	<?php endif;?>
	
	<?php if ($_POST['page'] == 'manage'):?>
		<section class="manage">
			<h3 class="title"><a href="?page=manage" class="post">Manage</a></h3>
			<p>
				Manage website from this page
			</p>
			<hr>
			<ul class="grid-item">
				<a href="?page=orders" class="post item"><li>View orders</li></a>
				<a href="?page=product_catg_user_add" class="post item"><li>Add a products - categories - users</li></a>
				<a href="?page=modif_del" class="post item"><li>Modify / Delete a products - categories - users</li></a>
			</ul>
		</section>
	<?php endif;?>

	<?php if ($_POST['page'] == 'orders'):?>
		<section class="orders">
			<h3 class="title"><a href="?page=manage" class="post">Orders</a></h3>
			<?php if ($_POST['id'] !== null):?>
				<p>
					<?="User ".$order["user"]." purchase ".$order['cart']['price']." $ on ".date("F j, Y", $order["timestamp"])." at ".date("g:i a", $order["timestamp"])?>
				</p>
				<hr>
				<ul class="grid-item">
					<?php foreach ($order['cart'] as $key => $product):?>
						<?php if (is_string($key)) continue;?>
						<a href="?page=products&id=<?=$product['id']?>" title="details" class="post item">
							<li><?=$product['name']?> <span><?=$product['price']." $  x  ".$product['quantity']?></span></li>
						</a>
					<?php endforeach;?>
				</ul>
			<?php else:?>
				<p>
					See the last orders
				</p>
				<hr>
				<ul class="grid-item">
					<?php foreach (get_orders() as $id=>$order):?>
					<li class="item"><a href="?page=orders&id=<?=$id?>" class="post"><h4>
						<?=$order["user"]." - ".date("F j, Y, g:i a", $order["timestamp"])?>
					</h4></a></li>
					<?php endforeach;?>
				</ul>
			<?php endif;?>
		</section>
	<?php endif;?>

	<?php if ($_POST['page'] == 'modif_del'):?>
		<?php if ($_POST['id'] !== null):?>
			<section class="product_id_modif_del">
				<h3 class="title"><a href="?page=modif_del" class="post">Modify a product</a></h3>
				<p>
					Modify or delete a product
				</p>
				<hr>
				<form action="" method="post" id="form_product_modif">
					<ul class="grid-item">
						<li>Name:<input type="text" name="name" value="<?=$product["name"]?>"></li>
						<li>Description:<input type="text" name="description" value="<?=$product["description"]?>"></li>
						<li>Price ($):<input type="number" step="any" name="price" value="<?=$product["price"]?>"></li>
						<li>Colorable:<input type="checkbox" name="colorable" <?=($product['colorable'] ? "checked" : "" )?>></li>
						<li>Categories:
							<select name="categories[]" size="4" multiple="multiple">
							<?php foreach(get_categories() as $category):?>
								<option <?=(array_search($category, $product['categories']) !== false ? "selected" : "")?> value="<?=$category?>"><?=$category?></option>
							<?php endforeach;?>
							</select>
						</li>
						<li><div>
							<a href="?form_product_modif=product_modified" class="submit btn">Save</a>
							<a href="?form_product_modif=product_delete" class="submit btn right delete">Delete</a>
						</div></li>
						<input type="hidden" name="product_id" value="<?=$product['id']?>">
						<input type="hidden" name="page">
					</ul>
				</form>
			</section>
		<?php elseif ($_POST['category'] !== null):?>
			<section class="category_name_modif_del">
				<h3 class="title"><a href="?page=modif_del" class="post">Modify a category</a></h3>
				<p>
					Modify or delete a category
				</p>
				<hr>
				<form action="" method="post" id="form_category_modif">
					<ul class="grid-item">
						<li>Name:<input type="text" disabled name="name" placeholder="<?=$category?>"></li>
						<li><div>
							<a style="visibility: hidden" class="submit btn">Save</a>
							<a href="?form_category_modif=category_delete" class="submit btn right delete">Delete</a>
						</div></li>
						<input type="hidden" name="name" value="<?=$category?>">
						<input type="hidden" name="page">
					</ul>
				</form>
			</section>
		<?php elseif ($_POST['login'] !== null):?>
			<section class="user_login_modif_del">
				<h3 class="title"><a href="?page=modif_del" class="post">Modify an user</a></h3>
				<p>
					Modify or delete a user
				</p>
				<hr>
				<form action="" method="post" id="form_user_modif">
					<ul class="grid-item">
						<li>Login:<input type="text" disabled placeholder="<?=$user["login"]?>"></li>
						<li>New password:<input type="password" name="newpasswd" placeholder="Leave empty to not modify"></li>
						<li>Address:<input type="text" name="address" value="<?=$user["address"]?>"></li>
						<li><div>
							<a href="?form_user_modif=user_modified" class="submit btn">Save</a>
							<a href="?form_user_modif=user_delete" class="submit btn right delete">Delete</a>
						</div></li>
						<input type="hidden" name="login" value="<?=$user["login"]?>">
						<input type="hidden" name="page">
					</ul>
				</form>
			</section>
		<?php else:?>
			<section class="product_modif_del">
				<h3 class="title"><a href="?page=manage" class="post">Product managment</a></h3>
				<p>
					Chose product to modify or delete
				</p>
				<hr>
				<ul class="grid-item">
					<?php foreach (get_products() as $product):?>
						<a href="?page=modif_del&id=<?=$product['id']?>" class="post item" style="background-image: url(img/products/<?=$product['id']?>.png);">
							<p class="product"><?=$product['name']?>
								<span><?=$product['price']?> $</span>
								<span><?=$product['categories'][0]?></span>
							</p>
							<p class="description"><?=$product['description']?></p>
						</a>
					<?php endforeach;?>
				</ul>
			</section>
			<section class="categories">
				<h3 class="title"><a href="?page=manage" class="post">Categories managment</a></h3>
				<p>
					Chose a category to modify or delete
				</p>
				<hr>
				<ul class="grid-item">
					<?php foreach (get_categories() as $category):?>
						<li><a href="?page=modif_del&category=<?=$category?>" class="post"><h4>[ <?=$category?> ]</h4></a></li>
					<?php endforeach;?>
				</ul>
			</section>
			<section class="categories user_modif_del">
				<h3 class="title"><a href="?page=manage" class="post">User managment</a></h3>
				<p>
					Chose a user to modify or delete
				</p>
				<hr>
				<ul class="grid-item">
					<?php foreach (get_users() as $user):?>
						<li><a href="?page=modif_del&login=<?=$user['login']?>" class="post"><h4><?=$user['login']?></h4></a></li>
					<?php endforeach;?>
				</ul>
			</section>
		<?php endif;?>
	<?php endif;?>
	
	<?php if ($_POST['page'] == 'product_catg_user_add'):?>
		<section class="product_add">
			<h3 class="title"><a href="?page=manage" class="post">Add a product</a></h3>
			<p>
				Add a product
			</p>
			<hr>
			<form action="" method="post" id="form_product">
				<ul class="grid-item">
					<li>Name:<input type="text" name="name"></li>
					<li>Description:<input type="text" name="description"></li>
					<li>Price ($):<input type="number" step="any" name="price"></li>
					<li>Colorable:<input type="checkbox" name="colorable"></li>
					<li>Categories:
						<select name="categories[]" size="4" multiple="multiple">
						<?php foreach(get_categories() as $category):?>
							<option value="<?=$category?>"><?=$category?></option>
						<?php endforeach;?>
						</select>
					</li>
					<li><div>
						<a href="?form_product=product_add" class="submit btn">Save</a>
					</div></li>
					<input type="hidden" name="page">
				</ul>
			</form>
		</section>
		<section class="category_add">
			<h3 class="title"><a href="?page=manage" class="post">Add a category</a></h3>
			<p>
				Add a category
			</p>
			<hr>
			<form action="" method="post" id="form_category">
				<ul class="grid-item">
					<li>Name:<input type="text" name="name"></li>
					<li><div>
						<a href="?form_category=category_add" class="submit btn">Save</a>
					</div></li>
					<input type="hidden" name="page">
				</ul>
			</form>
		</section>
		<section class="user_add">
			<h3 class="title"><a href="?page=manage" class="post">Add an user</a></h3>
			<p>
				Add a user
			</p>
			<hr>
			<form action="" method="post" id="form_user">
				<ul class="grid-item">
					<li>Login:<input type="text" name="login"></li>
					<li>Password:<input type="password" name="password"></li>
					<li><div>
						<a href="?form_user=user_add" class="submit btn">Save</a>
					</div></li>
					<input type="hidden" name="page">
				</ul>
			</form>
		</section>
	<?php endif;?>
	
	</?php D($_POST, $_SESSION);?>
	<footer>
		<p>Made by <a href="//profile.intra.42.fr/users/tbailleu" target="_blank">tbailleu</a> & <a href="//profile.intra.42.fr/users/aledru" target="_blank">aledru</a>.</p>
	</footer>
	<script src="script.js" async></script>
</body>
</html>
