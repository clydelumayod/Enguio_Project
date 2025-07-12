// pages/pos.js
"use client";
import { useState, useEffect } from 'react';

export default function POS() {
  const [products, setProducts] = useState([]);
  const [cart, setCart] = useState([]);
  const [searchTerm, setSearchTerm] = useState('');
  const [selectedCategory, setSelectedCategory] = useState('All');
  const [total, setTotal] = useState(0);
  const [quantityInputs, setQuantityInputs] = useState({});
  const [selectedIndex, setSelectedIndex] = useState(0); // For product grid
  const [navigationIndex, setNavigationIndex] = useState(0); // For layout sections

  const mockProducts = [
    { id: 1, name: "Paracetamol 500mg", price: 15.00, quantity: 100, category: "Medicine" },
    { id: 2, name: "Amoxicillin 250mg", price: 35.00, quantity: 80, category: "Medicine" },
    { id: 3, name: "Cough Syrup 100ml", price: 45.00, quantity: 60, category: "Medicine" },
    { id: 4, name: "Coca-Cola 300ml", price: 25.00, quantity: 50, category: "Beverages" },
    { id: 5, name: "Pepsi 300ml", price: 25.00, quantity: 45, category: "Beverages" },
    { id: 6, name: "Chips - Salted", price: 15.00, quantity: 70, category: "Snacks" },
    { id: 7, name: "Milk 1L", price: 50.00, quantity: 30, category: "Dairy" },
  ];

  // Initialize data
  useEffect(() => {
    setProducts(mockProducts);
    const savedCart = JSON.parse(localStorage.getItem('pos-cart'));
    if (savedCart) setCart(savedCart);
  }, []);

  // Calculate total
  useEffect(() => {
    const newTotal = cart.reduce(
      (acc, item) => acc + (item.product.price * item.quantity),
      0
    );
    setTotal(newTotal);
    localStorage.setItem('pos-cart', JSON.stringify(cart));
  }, [cart]);

  // Add to cart with custom quantity
  const addToCart = (product, quantity) => {
    if (quantity <= 0 || quantity > product.quantity) {
      alert(`Please enter a valid quantity (1–${product.quantity})`);
      return;
    }
    setCart(prevCart => {
      const existingItem = prevCart.find(item => item.product.id === product.id);
      if (existingItem) {
        return prevCart.map(item =>
          item.product.id === product.id
            ? { ...item, quantity: item.quantity + quantity }
            : item
        );
      } else {
        return [...prevCart, { product, quantity }];
      }
    });
    setQuantityInputs(prev => ({
      ...prev,
      [product.id]: 1
    }));
  };

  // Filter products based on search term and selected category
  const filteredProducts = mockProducts.filter(product =>
    product.name.toLowerCase().includes(searchTerm.toLowerCase()) &&
    (selectedCategory === 'All' || product.category === selectedCategory)
  );

  // Update quantity input when navigating
  useEffect(() => {
    if (filteredProducts[selectedIndex]) {
      const product = filteredProducts[selectedIndex];
      if (!quantityInputs[product.id]) {
        setQuantityInputs(prev => ({ ...prev, [product.id]: 1 }));
      }
    }
  }, [selectedIndex, filteredProducts]);

  // Keyboard Navigation (Layout + Product Grid)
  useEffect(() => {
    const handleKeyDown = (e) => {
      // Prevent navigation if user is typing in input
      if (
        ['INPUT', 'SELECT', 'TEXTAREA'].includes(document.activeElement.tagName)
      ) return;

      const cols = 3; // Number of columns in the grid
      const maxIndex = filteredProducts.length - 1;

      switch (e.key) {
        case 'ArrowUp':
        case 'w':
        case 'W':
          e.preventDefault();
          if (navigationIndex === 1 && selectedIndex - cols >= 0) {
            setSelectedIndex(prev => prev - cols);
          } else {
            setNavigationIndex(prev => Math.max(prev - 1, 0));
          }
          break;

        case 'ArrowDown':
        case 's':
        case 'S':
          e.preventDefault();
          if (navigationIndex === 1 && selectedIndex + cols <= maxIndex) {
            setSelectedIndex(prev => prev + cols);
          } else {
            setNavigationIndex(prev => Math.min(prev + 1, 3)); // 0: Search, 1: Products, 2: Cart, 3: Checkout
          }
          break;

        case 'ArrowLeft':
        case 'a':
        case 'A':
          if (navigationIndex === 1 && selectedIndex > 0) {
            e.preventDefault();
            setSelectedIndex(prev => prev - 1);
          }
          break;

        case 'ArrowRight':
        case 'd':
        case 'D':
          if (navigationIndex === 1 && selectedIndex < maxIndex) {
            e.preventDefault();
            setSelectedIndex(prev => prev + 1);
          }
          break;

        case 'Enter':
          e.preventDefault();

          if (navigationIndex === 0) {
            // Focus search bar
            document.getElementById('search-input')?.focus();
          } else if (navigationIndex === 1 && filteredProducts[selectedIndex]) {
            // Add selected product to cart
            const product = filteredProducts[selectedIndex];
            const quantity = quantityInputs[product.id] || 1;
            addToCart(product, quantity);
          } else if (navigationIndex === 2) {
            // Scroll to cart
            document.querySelector('.cart-section')?.scrollIntoView({ behavior: 'smooth' });
          } else if (navigationIndex === 3) {
            // Trigger checkout
            handleCheckout();
          }
          break;

        default:
          break;
      }
    };

    window.addEventListener('keydown', handleKeyDown);
    return () => window.removeEventListener('keydown', handleKeyDown);
  }, [navigationIndex, selectedIndex, filteredProducts, quantityInputs]);

  // Cart functions
  const updateCartItemQuantity = (productId, newQuantity) => {
    if (newQuantity < 1) {
      removeFromCart(productId);
      return;
    }
    setCart(prevCart =>
      prevCart.map(item =>
        item.product.id === productId
          ? { ...item, quantity: newQuantity }
          : item
      )
    );
  };

  const removeFromCart = (productId) => {
    setCart(prevCart =>
      prevCart.filter(item => item.product.id !== productId)
    );
  };

  const handleCheckout = () => {
    alert(`Transaction Successful! Total: ₱${total.toFixed(2)}`);
    setCart([]);
    localStorage.removeItem('pos-cart');
  };

  return (
    <div className="min-h-screen w-full bg-gray-100">
      <div className="max-w-9xl mx-auto bg-white shadow-lg h-full">
        {/* Header */}
        <div className="bg-blue-600 p-4 text-white">
          <h1 className="text-2xl font-bold">Enguios Pharmacy & Convenience Store</h1>
          <p>Integrated Point of Sale System</p>
        </div>

        {/* Layout */}
        <div className="flex flex-col md:flex-row h-full">
          {/* Left Side - Product Search & Selection */}
          <div className="md:w-2/3 p-4 border-r h-full">
            {/* Section 0: Search Bar */}
            <div className="mb-4">
              <input
                id="search-input"
                type="text"
                placeholder="Search products..."
                value={searchTerm}
                onChange={(e) => setSearchTerm(e.target.value)}
                className={`w-full px-4 py-2 border rounded-lg ${
                  navigationIndex === 0 ? 'ring-2 ring-blue-500' : ''
                }`}
              />
            </div>

            {/* Category Dropdown */}
            <div className="mb-4">
              <select
                value={selectedCategory}
                onChange={(e) => setSelectedCategory(e.target.value)}
                className={`px-4 py-2 border rounded-lg ${
                  navigationIndex === 0 ? 'ring-2 ring-blue-500' : ''
                }`}
              >
                <option value="All">All Categories</option>
                <option value="Medicine">Medicine</option>
                <option value="Beverages">Beverages</option>
                <option value="Snacks">Snacks</option>
                <option value="Dairy">Dairy</option>
              </select>
            </div>

            {/* Section 1: Product Grid */}
            <div
              className={`grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 overflow-y-auto h-[30rem] ${
                navigationIndex === 1 ? 'ring-2 ring-blue-500 p-2 rounded-lg' : ''
              }`}
            >
              {filteredProducts.map((product, index) => (
                <div
                  key={product.id}
                  className="flex justify-center"
                >
                  <div
                    className={`border p-4 rounded-lg hover:shadow-md transition-shadow w-full max-w-sm ${
                      navigationIndex === 1 && selectedIndex === index ? 'ring-2 ring-blue-500' : ''
                    }`}
                  >
                    <h3 className="font-semibold">{product.name}</h3>
                    <p className="text-blue-600 font-medium">₱{product.price.toFixed(2)}</p>
                    <p className="text-sm text-gray-500">Stock: {product.quantity}</p>
                    <div className="mt-2 flex">
                      <input
                        type="number"
                        min="1"
                        max={product.quantity}
                        value={quantityInputs[product.id] || 1}
                        readOnly
                        className="w-16 px-2 py-1 border rounded-l"
                      />
                      <button
                        onClick={() => addToCart(product, quantityInputs[product.id] || 1)}
                        className="flex-1 bg-blue-500 text-white py-1 rounded-r hover:bg-blue-600"
                      >
                        Add to Cart
                      </button>
                    </div>
                  </div>
                </div>
              ))}
            </div>
          </div>

          {/* Right Side - Cart & Checkout */}
          <div className="md:w-1/3 p-4 h-full">
            {/* Section 2: Cart */}
            <div
              className={`bg-gray-50 rounded-lg p-4 mb-4 ${
                navigationIndex === 2 ? 'ring-2 ring-blue-500' : ''
              } cart-section`}
            >
              <h2 className="text-xl font-bold mb-4">Cart ({cart.length} items)</h2>
              {cart.length === 0 ? (
                <div className="text-center py-8 text-gray-500">
                  <p>Your cart is empty</p>
                </div>
              ) : (
                <ul className="mb-4 max-h-40 overflow-y-auto">
                  {cart.map(item => (
                    <li key={item.product.id} className="py-2 border-b">
                      <div className="flex justify-between">
                        <span className="font-medium">{item.product.name}</span>
                        <span className="font-medium">
                          ₱{(item.product.price * item.quantity).toFixed(2)}
                        </span>
                      </div>
                    </li>
                  ))}
                </ul>
              )}
            </div>

            {/* Section 3: Checkout Button */}
            <button
              id="checkout-button"
              onClick={handleCheckout}
              className={`w-full py-3 rounded-lg text-white font-semibold ${
                navigationIndex === 3 ? 'bg-green-700 ring-2 ring-green-500' : 'bg-green-500 hover:bg-green-600'
              }`}
            >
              Checkout - ₱{total.toFixed(2)}
            </button>
          </div>
        </div>
      </div>
    </div>
  );
}