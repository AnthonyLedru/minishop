<?php
	session_start();
	date_default_timezone_set("Europe/Paris");

	function D(...$args) {
		echo '<pre style="background-color: #5f3b">';
		foreach ($args as $arg) {
			var_dump($arg);
		}
		echo '</pre>';
	}	

	function check_database($dbname) {
		if (!file_exists('./private'))
			mkdir('./private');
		if (!file_exists('./private/'.$dbname) || unserialize(file_get_contents('./private/'.$dbname)) === false)
			file_put_contents('./private/'.$dbname, serialize(array()), LOCK_EX);
		return unserialize(file_get_contents('./private/'.$dbname));
	}

	function addto_database($dbname, $data) {
		if (($table = check_database($dbname)) === false)
			return false;
		$table[] = $data;
		file_put_contents('./private/'.$dbname, serialize($table), LOCK_EX);
		return $table;
	}

    function connect_user($login, $passwd) {
		$users = check_database('passwd');
		if ($login === '')
			return "Username cannot be empty";
		$login = htmlentities($login);
		if ($passwd === '')
			return "Password cannot be empty";
		foreach ($users as $id => $user) {
			if ($user['login'] === $login) {
				if ($user['passwd'] === hash("sha256", $passwd.$passwd))
					return array(
						'login'=>$user['login'],
						'address'=>$user['address'],
						'admin'=>$user['admin']
					);
				else
					return "Password incorrect";
			}
		}
		return "User cannot be found";
	}
	
	function get_users()
	{
		$users = check_database('passwd');
		return ($users);
	}

	function get_user_by_login($login)
	{
		$users = check_database('passwd');
		foreach ($users as $user)
			if ($user['login'] === $login)
				return $user;
		return "";
	}

	function new_user($login, $passwd, $admin = false) {
		$users = check_database('passwd');
		if ($login === '')
			return "Username cannot be empty";
		$login = htmlentities($login);
		if ($passwd === '')
			return "Password cannot be empty";
		foreach ($users as $id => $user) {
			if ($user['login'] === $login)
				return "Username already taken";
		}
		$user = array(
			'login'=>$login,
			'address'=>false,
			'passwd'=>hash("sha256", $passwd.$passwd),
			'admin'=>$admin
		);
		if ((addto_database('passwd', $user)) === false)
			return "Database error in filesystem";
		return array('login'=>$user['login'], 'address'=>$user['address'], 'admin'=>$user['admin']);
	}

	function modify_user($login, $address, $passwd, $newpasswd, $manage = false) {
		$users = check_database('passwd');
		if ($address === '')
			return "Address cannot be empty";
		$login = htmlentities($login);
		if (!$manage)
			if (!is_array($return = connect_user($login, $passwd)))
				return $return;
		if ($login)
			foreach ($users as $id => $user) {
				if ($user['login'] === $login) {
					$users[$id]['address'] = $address;
					if (!empty($newpasswd))
						$users[$id]['passwd'] = hash("sha256", $newpasswd.$newpasswd);
					file_put_contents('./private/passwd', serialize($users), LOCK_EX);
					return array(
						'login'=>$user['login'],
						'address'=>$address,
						'admin'=>$user['admin']
					);
				}
			}
		else
			return "Login cannot be empty";
		return "Database error in filesystem";
	}

	function delete_user($login, $passwd, $manage = false) {
		$users = check_database('passwd');
		if (!$manage)
			if (!is_array($return = connect_user($login, $passwd)))
				return $return;
		if ($login)
			foreach ($users as $id => $user) {
				if ($user['login'] === $login) {
					if ($user['admin'])
						return "Cannot delete admin user";
					array_splice($users, $id, 1);
					file_put_contents('./private/passwd', serialize($users), LOCK_EX);
					return true;
				}
			}
		else
			return "Login cannot be empty";
		return "Database error in filesystem";
	}

	function new_category($category) {
		if ($category) {
			$category = htmlentities($category);
			$categories = check_database('categories');
			if (array_search($category, $categories) !== false)
				return "Category $category already exist";
			if ((addto_database("categories", $category)) === false)
				return "Database error in filesystem";
			return $category;
		}
		return "Category name cannot be empty";
	}

	function delete_category($category) {
		if ($category) {
			$category = htmlentities($category);
			$categories = check_database('categories');
			if (array_search($category, $categories) !== false)
				foreach ($categories as $id => $catg) {
					if ($category === $catg) {
						array_splice($categories, $id, 1);
						file_put_contents('./private/categories', serialize($categories), LOCK_EX);
						return true;
					}
				}
			else
				return "Category $category doesn't exist";
		}
		return "Category name cannot be empty";
	}

	function new_product($name, $categories, $description, $price, $colorable = false) {
		if ($name === '' || $description === '' || $categories === null || $price === '')
			return "Error there is empty variable";
		$name = htmlentities($name);
		$description = htmlentities($description);
		$products = check_database('products');
		$product = array(
			'id'=>count($products),
			'name'=>$name,
			'categories'=>$categories,
			'description'=>$description,
			'price'=>$price,
			'colorable'=>$colorable
		);
		if ((addto_database('products', $product)) === false)
			return "Database error in filesystem";
		return $product;
	}

	function modify_product($id, $name, $categories, $description, $price, $colorable = false) {
		if ($name == '' || $description == '' || $categories === null || $price == '')
			return "Error there is empty variable";
		$name = htmlentities($name);
		$description = htmlentities($description);
		$products = check_database('products');
		foreach ($products as $key => $product) {
			if ($product['id'] === $id) {
				$products[$id]['name'] = $name;
				$products[$id]['categories'] = $categories;
				$products[$id]['description'] = $description;
				$products[$id]['price'] = $price;
				$products[$id]['colorable'] = $colorable;
				file_put_contents('./private/products', serialize($products), LOCK_EX);
				return array(
					'id'=>$id,
					'name'=>$name,
					'categories'=>$categories,
					'description'=>$description,
					'price'=>$price,
					'colorable'=>$colorable
				);
			}
		}
		return "Product_id: $id, not found in database";
	}

	function delete_product($id) {
		$products = check_database('products');
		foreach ($products as $key => $product) {
			if ($product['id'] === $id) {
				array_splice($products, $key, 1);
				file_put_contents('./private/products', serialize($products), LOCK_EX);
				return true;
			}
		}
		return "Product_id: $id, not found in database";
	}

	function get_product_by_id($id = 0) {
		$products = check_database('products');
		return $products[$id];
	}

	function get_products($nbr = -1) {
		$products = check_database('products');
		if ($nbr === -1)
			$nbr = count($products);
		$nbr = min($nbr, count($products));
		return array_reverse(array_slice($products, -$nbr, $nbr));
	}

	function extract_categories() {
		$products = get_products(4);
		$categories = array();
		foreach ($products as $product)
			foreach ($product['categories'] as $category)
				$categories[] = $category;
		$categories = array_unique($categories);
		file_put_contents('./private/categories', serialize($categories), LOCK_EX);
		return $categories;
	}

	function get_categories() {
		return check_database('categories');
	}

	function get_category_by_name($name)
	{
		$categories = check_database("categories");
		foreach ($categories as $category)
			if ($category === urldecode($name))
				return $category;
		D($categories, $name);
		return "This category doesn't exist";
	}

	function get_products_by_category($category = "") {
		$result = array();
		$products = check_database('products');
		foreach ($products as $product)
			if (array_search($category, $product['categories']) !== false)
				$result[] = $product;
		return $result;
	}

	function addto_cart($product) {
		unset($product['description']);
		if (($cart = $_SESSION['cart']) === null)
			$cart = array('quantity'=>0, 'price'=>0);
		foreach ($cart as $key => $item) {
			if ($item['id'] === $product['id']) {
				$cart[$key]['quantity']++;
				$cart['quantity']++;
				$cart['price'] += $product['price'];
				return $cart;
			}
		}
		$product['quantity'] = 1;
		$cart['quantity']++;
		$cart['price'] += $product['price'];
		array_push($cart, $product);
		return $cart;
	}

	function delfrom_cart($product) {
		if (($cart = $_SESSION['cart']) === null)
			$cart = array('quantity'=>0, 'price'=>0);
		foreach ($cart as $key => $item) {
			if (is_string($key)) continue;
			if ($item['id'] === $product['id']) {
				$cart[$key]['quantity']--;
				$cart['quantity']--;
				$cart['price'] -= $product['price'];
				if ($cart[$key]['quantity'] == 0)
					array_splice($cart, $key+2, 1);
				if ($cart['quantity'] == 0)
					$cart = array();
				return $cart;
			}
		}
	}

	function new_order($cart, $user) {
		if ($cart && $user) {
			$order = array(
				"user"=>$user["login"],
				"address"=>$user["address"],
				"cart"=>$cart,
				"timestamp"=>time()
			);
			if (addto_database('orders', $order) !== false)
				return ($order);
			}
		return false;
	}

	function get_orders($id = -1) {
		$orders = check_database("orders");
		if ($id !== -1)
			return $orders[$id];
		return $orders;
	}
