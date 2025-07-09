"use client";

import { useState } from "react";
import { useRouter } from "next/navigation";
import axios from "axios";

export default function LoginForm() {
  const [username, setUsername] = useState("");
  const [password, setPassword] = useState("");
  const [error, setError] = useState("");
  const router = useRouter();

  const handleSubmit = async (e) => {
    e.preventDefault();
    setError("");

    // Client-side validation
    if (!username.trim() || !password.trim()) {
      setError("Both username and password are required");
      return;
    }

    try {
      const res = await axios.post("http://localhost/api2/index.php", {
        action: "login", // Ensure lowercase to match backend
        username: username,
        password: password,
      });

      if (res.data.success) {
        const role = res.data.role;

        // Redirect based on role
        switch (role) {
          case "admin":
            router.push("/admin");
            break;
          case "cashier":
            router.push("/cashier");
            break;
          case "pharmacist":
            router.push("/pharmacist");
            break;
          case "inventory":
            router.push("/inventory");
            break;
          default:
            setError("Unauthorized role.");
        }
      } else {
        setError(res.data.message || "Invalid username or password.");
      }
    } catch (err) {
      console.error("Login error:", err);
      setError("An unexpected error occurred.");
    }
  };

  return (
    <div className="flex items-center justify-center min-h-screen bg-teal-500">
      <form
        onSubmit={handleSubmit}
        className="w-full max-w-md p-8 space-y-6 bg-green-100 rounded-xl shadow-lg"
      >
        <h2 className="text-2xl font-bold text-center text-gray-800">Login</h2>

        {error && <p className="text-red-500 text-center">{error}</p>}

        <div>
          <label htmlFor="username" className="block text-sm font-medium text-gray-700">
            Username
          </label>
          <input
            type="text"
            id="username"
            value={username}
            onChange={(e) => setUsername(e.target.value)}
            required
            className="w-full px-4 py-3 mt-1 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
          />
        </div>

        <div>
          <label htmlFor="password" className="block text-sm font-medium text-gray-700">
            Password
          </label>
          <input
            type="password"
            id="password"
            value={password}
            onChange={(e) => setPassword(e.target.value)}
            required
            className="w-full px-4 py-3 mt-1 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
          />
        </div>

        <button
          type="submit"
          className="w-full px-4 py-3 text-white bg-teal-600 rounded-md hover:bg-teal-700 transition duration-200"
        >
          Login
        </button>
      </form>
    </div>
  );
}