<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Facilities Management</title>
    <script src="../assets/htmx.min.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 h-screen flex items-center justify-center">
    <div class="w-full max-w-md">
        <div class="bg-white shadow-lg rounded-lg px-8 pt-6 pb-8 mb-4">
            <div class="mb-6 text-center">
                <h1 class="text-2xl font-bold text-gray-800">FM App Login</h1>
                <p class="text-gray-600 mt-2">Please sign in to your account</p>
            </div>

            <div id="login-error"></div>

            <form hx-post="../backend/auth.php" hx-target="#login-error" hx-swap="innerHTML">
                <input type="hidden" name="action" value="login">
                
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="username">
                        Username
                    </label>
                    <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" 
                           id="username" name="username" type="text" placeholder="Username" required>
                </div>
                
                <div class="mb-6">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="password">
                        Password
                    </label>
                    <input class="shadow appearance-none border border-red-500 rounded w-full py-2 px-3 text-gray-700 mb-3 leading-tight focus:outline-none focus:shadow-outline" 
                           id="password" name="password" type="password" placeholder="******************" required>
                </div>
                
                <div class="flex items-center justify-between">
                    <button class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline w-full transition duration-300" 
                            type="submit">
                        Sign In
                    </button>
                </div>
            </form>
            <div class="mt-4 text-center">
                <p class="text-xs text-gray-500">Default: admin / admin123</p>
            </div>
        </div>
        <p class="text-center text-gray-500 text-xs">
            &copy; 2024 Facilities Management App. All rights reserved.
        </p>
    </div>
</body>
</html>