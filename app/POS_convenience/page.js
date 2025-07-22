// pages/pos.js
"use client";
import { useState, useEffect, useRef } from 'react';

export default function POS() {
  const [products, setProducts] = useState([]);
  const [cart, setCart] = useState([]);
  const [searchTerm, setSearchTerm] = useState('');
  const [selectedCategory, setSelectedCategory] = useState('All');
  const [total, setTotal] = useState(0);
  const [quantityInputs, setQuantityInputs] = useState({});
  const [selectedIndex, setSelectedIndex] = useState(0); // For product grid
  const [navigationIndex, setNavigationIndex] = useState(0); // 0: Search, 1: Products, 2: Checkout
  const [paymentMethod, setPaymentMethod] = useState(''); // 'cash' or 'gcash'
  const [amountPaid, setAmountPaid] = useState('');
  const [referenceNumber, setReferenceNumber] = useState('');
  const [showRefInput, setShowRefInput] = useState(false);
  const [change, setChange] = useState(0);
  const [checkoutFocusIndex, setCheckoutFocusIndex] = useState(0); // 0: Amount, 1: Cash, 2: GCash, 3: Ref, 4: Checkout
  const amountPaidRef = useRef(null);
  const cashBtnRef = useRef(null);
  const gcashBtnRef = useRef(null);
  const refNumRef = useRef(null);
  const checkoutBtnRef = useRef(null);
  const prevNavigationIndex = useRef(navigationIndex);
  const [cartFocusIndex, setCartFocusIndex] = useState(0);
  const justBlurredAmountPaid = useRef(false);
  const [showThankYouModal, setShowThankYouModal] = useState(false);

  useEffect(() => {
    if (amountPaid && !isNaN(amountPaid)) {
      setChange(parseFloat(amountPaid) - total);
    } else {
      setChange(0);
    }
  }, [amountPaid, total]);

  useEffect(() => {
    if (navigationIndex === 2) {
      requestAnimationFrame(() => {
        if (checkoutFocusIndex === 0 && amountPaidRef.current) {
          amountPaidRef.current.focus();
        } else if (checkoutFocusIndex === 1 && cashBtnRef.current) {
          cashBtnRef.current.focus();
        } else if (checkoutFocusIndex === 2 && gcashBtnRef.current) {
          gcashBtnRef.current.focus();
        } else if (checkoutFocusIndex === 3 && refNumRef.current) {
          refNumRef.current.focus();
        } else if (checkoutFocusIndex === 4 && checkoutBtnRef.current) {
          checkoutBtnRef.current.focus();
        }
      });
    }
  }, [navigationIndex, checkoutFocusIndex, paymentMethod]);

  useEffect(() => {
    if (navigationIndex === 2 && prevNavigationIndex.current !== 2) {
      setCheckoutFocusIndex(0);
    }
    prevNavigationIndex.current = navigationIndex;
  }, [navigationIndex]);

  useEffect(() => {
    if (navigationIndex === 2) {
      if (cart.length === 0 && checkoutFocusIndex !== 4) {
        setCheckoutFocusIndex(4); // Only checkout button is focusable
      } else if (cart.length > 0 && checkoutFocusIndex === 4) {
        setCheckoutFocusIndex(0); // Start at Amount Paid when cart is filled
      }
    } else if (navigationIndex === 1 && checkoutFocusIndex !== 0) {
      setCheckoutFocusIndex(0); // Reset focus when navigating to product card
    }
  }, [cart.length, navigationIndex]);

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

  // Keyboard Navigation (Search, Products, Checkout)
  useEffect(() => {
    const handleKeyDown = (e) => {
      // Prevent navigation if user is typing in input
      if (
        ['INPUT', 'SELECT', 'TEXTAREA'].includes(document.activeElement.tagName)
      ) return;

      const cols = 3; // Number of columns in the grid
      const maxIndex = filteredProducts.length - 1;

      // --- Cart adjustment navigation ---
      if (navigationIndex === 2 && checkoutFocusIndex === 'cart' && cart.length > 0) {
        if (["ArrowDown", "s", "S"].includes(e.key)) {
          e.preventDefault();
          if (cartFocusIndex === cart.length - 1) {
            setCheckoutFocusIndex(4); // Move to checkout button
          } else {
            setCartFocusIndex((prev) => prev + 1);
          }
          return;
        }
        if (["ArrowUp", "w", "W"].includes(e.key)) {
          e.preventDefault();
          setCartFocusIndex((prev) => (prev - 1 + cart.length) % cart.length);
          return;
        }
        if (e.ctrlKey && ["ArrowLeft"].includes(e.key)) {
          e.preventDefault();
          updateCartItemQuantity(cart[cartFocusIndex].product.id, cart[cartFocusIndex].quantity - 1);
          return;
        }
        if (e.ctrlKey && ["ArrowRight"].includes(e.key)) {
          e.preventDefault();
          updateCartItemQuantity(cart[cartFocusIndex].product.id, cart[cartFocusIndex].quantity + 1);
          return;
        }
        if (["Tab", "Enter"].includes(e.key)) {
          e.preventDefault();
          setCheckoutFocusIndex(0); // Move to Amount Paid input
          return;
        }
        return;
      }
      // --- End cart adjustment navigation ---

      // --- Checkout navigation ---
      if (navigationIndex === 2) {
        let maxFocus = cart.length > 0 ? 4 : 3; // Always allow access to checkout button (index 4) when cart has items
        if (["ArrowDown", "s", "S"].includes(e.key) && checkoutFocusIndex === 0 && justBlurredAmountPaid.current) {
          e.preventDefault();
          setCheckoutFocusIndex(4); // Move to checkout button
          justBlurredAmountPaid.current = false;
          return;
        }
        if (["Tab", "ArrowDown", "s", "S"].includes(e.key)) {
          e.preventDefault();
          if (checkoutFocusIndex === 3) {
            setCheckoutFocusIndex(4);
            return;
          }
          // If currently on Cash button, go directly to Checkout button
          if (checkoutFocusIndex === 1) {
            setCheckoutFocusIndex(4);
            return;
          }
          // If cart is present and not already in cart focus, go to cart first
          if (cart.length > 0 && checkoutFocusIndex !== 'cart') {
            setCheckoutFocusIndex('cart');
            setCartFocusIndex(0);
            return;
          }
          setCheckoutFocusIndex((prev) => {
            if (prev + 1 > maxFocus) {
              return 0;
            } else {
              return prev + 1;
            }
          });
          return;
        }
        if (["ArrowUp", "w", "W"].includes(e.key)) {
          e.preventDefault();
          // If on checkout button, move to previous logical field
          if (checkoutFocusIndex === 4) {
            if (paymentMethod === 'gcash' && showRefInput) {
              setCheckoutFocusIndex(3); // GCash Ref
            } else {
              setCheckoutFocusIndex(0); // Amount Paid
            }
            return;
          }
          setCheckoutFocusIndex((prev) => (prev - 1) < 0 ? maxFocus : prev - 1);
          return;
        }
        // New: Left/Right arrows toggle between Cash and GCash buttons
        if (["ArrowLeft", "a", "A", "ArrowRight", "d", "D"].includes(e.key)) {
          if (checkoutFocusIndex === 1) { // Cash button
            e.preventDefault();
            setCheckoutFocusIndex(2); // Move to GCash
            return;
          }
          if (checkoutFocusIndex === 2) { // GCash button
            e.preventDefault();
            setCheckoutFocusIndex(1); // Move to Cash
            return;
          }
          // Only allow navigation back to product side from input or checkout button
          if (["ArrowLeft", "a", "A"].includes(e.key) && (checkoutFocusIndex === 0 || checkoutFocusIndex === maxFocus)) {
            setNavigationIndex(1);
            return;
          }
        }
        if (e.key === "Enter") {
          e.preventDefault();
          if (checkoutFocusIndex === 1) {
            setPaymentMethod('cash'); setShowRefInput(false);
          } else if (checkoutFocusIndex === 2) {
            setPaymentMethod('gcash'); setShowRefInput(true); setCheckoutFocusIndex(3);
          } else if (checkoutFocusIndex === 4 || (checkoutFocusIndex === 3 && paymentMethod !== 'gcash')) {
            handleCheckout();
          }
          return;
        }
        return;
      }
      // --- End checkout navigation ---

      switch (e.key) {
        case 'ArrowUp':
        case 'w':
        case 'W':
          e.preventDefault();
          if (navigationIndex === 1 && selectedIndex - cols >= 0) {
            // Move up in product grid
            setSelectedIndex(prev => prev - cols);
          } else if (navigationIndex === 2) {
            // Move from checkout to products
            setNavigationIndex(1);
          } else {
            // Move to previous section
            setNavigationIndex(prev => Math.max(prev - 1, 0));
          }
          break;

        case 'ArrowDown':
        case 's':
        case 'S':
          e.preventDefault();
          if (navigationIndex === 1 && selectedIndex + cols <= maxIndex) {
            // Move down in product grid
            setSelectedIndex(prev => prev + cols);
          } else if (navigationIndex === 0) {
            // Move from search to products
            setNavigationIndex(1);
          } else if (navigationIndex === 1) {
            // Move from products to checkout
            setNavigationIndex(2);
          }
          break;

        case 'ArrowLeft':
        case 'a':
        case 'A':
          e.preventDefault();
          if (navigationIndex === 1 && selectedIndex > 0) {
            // Move left in product grid
            setSelectedIndex(prev => prev - 1);
          } else if (navigationIndex === 2) {
            // Move from checkout to products
            setNavigationIndex(1);
          }
          break;

        case 'ArrowRight':
        case 'd':
        case 'D':
          e.preventDefault();
          if (navigationIndex === 1) {
            // Check if we're in the third column (rightmost position in the row)
            const currentRow = Math.floor(selectedIndex / cols);
            const currentCol = selectedIndex % cols;
            
            if (currentCol === 2) { // Third column (0-indexed, so 2 = third column)
              // If at the rightmost column, jump to checkout
              setNavigationIndex(2);
            } else {
              // Otherwise, move to the next card in the same row
              setSelectedIndex(prev => prev + 1);
            }
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
  }, [navigationIndex, selectedIndex, filteredProducts, quantityInputs, cart, cartFocusIndex]);

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

  const printReceipt = () => {
    // Get current date and time
    const now = new Date();
    const dateStr = now.toLocaleDateString();
    const timeStr = now.toLocaleTimeString();
    const transactionId = `TXN${now.getTime().toString().slice(-6)}`;

    // Create a new window for the receipt
    const printWindow = window.open('', '_blank', 'width=300,height=600');
    
    printWindow.document.write(`
      <html>
        <head>
          <title>Receipt</title>
          <style>
            body {
              font-family: 'Courier New', monospace;
              width: 300px;
              padding: 10px;
            }
            .center { text-align: center; }
            .header { font-size: 14px; font-weight: bold; }
            .items { margin: 10px 0; }
            .total { font-weight: bold; }
            .divider { border-top: 1px dashed #000; margin: 5px 0; }
            @media print {
              body { width: 100%; }
              .no-print { display: none; }
            }
          </style>
        </head>
        <body>
          <div class="center header">
            Enguios Pharmacy & Convenience Store
          </div>
          <div class="center">Receipt</div>
          <div class="divider"></div>
          <div>
            Date: ${dateStr}<br>
            Time: ${timeStr}<br>
            Transaction #: ${transactionId}
          </div>
          <div class="divider"></div>
          <div class="items">
            ${cart.map(item => `
              ${item.product.name}<br>
              &nbsp;&nbsp;${item.quantity} x ₱${item.product.price.toFixed(2)} = ₱${(item.product.price * item.quantity).toFixed(2)}
            `).join('<br>')}
          </div>
          <div class="divider"></div>
          <div class="total">
            Subtotal: ₱${total.toFixed(2)}<br>
            Payment Method: ${paymentMethod.toUpperCase()}<br>
            Amount Paid: ₱${amountPaid}<br>
            Change: ₱${change.toFixed(2)}<br>
            ${paymentMethod === 'gcash' ? `GCash Ref #: ${referenceNumber}<br>` : ''}
          </div>
          <div class="divider"></div>
          <div class="center">
            Thank you for shopping!<br>
            Please come again!
          </div>
          <button class="no-print" onclick="window.print(); window.close();" 
            style="margin-top: 20px; padding: 10px; width: 100%;">
            Print Receipt
          </button>
        </body>
      </html>
    `);
    
    printWindow.document.close();
  };

  const handleCheckout = () => {
    if (cart.length === 0) return;
    
    // Print receipt first
    printReceipt();
    
    // Clear cart and reset state
    setCart([]);
    localStorage.removeItem('pos-cart');
    setAmountPaid('');
    setReferenceNumber('');
    setPaymentMethod('');
    setShowRefInput(false);
    setShowThankYouModal(true);
  };

  useEffect(() => {
    if (showThankYouModal) {
      setTimeout(() => {
        setShowThankYouModal(false);
      }, 2000);
    }
  }, [showThankYouModal]);

  return (
    <>
      <style jsx>{`
        .scrollbar-hide {
          -ms-overflow-style: none;  /* Internet Explorer 10+ */
          scrollbar-width: none;  /* Firefox */
        }
        .scrollbar-hide::-webkit-scrollbar {
          display: none;  /* Safari and Chrome */
        }
      `}</style>
      <div className="min-h-screen w-full bg-gray-100">
        <div className="max-w-9xl mx-auto bg-white shadow-lg min-h-screen">
          {/* Header */}
          <div className="bg-blue-600 p-4 text-white">
            <h1 className="text-2xl font-bold">Enguios Pharmacy & Convenience Store</h1>
            <p>Integrated Point of Sale System</p>
          </div>

          {/* Layout */}
          <div className="flex flex-col md:flex-row flex-1">
            {/* Left Side - Product Search & Selection */}
            <div className="md:w-[85%] p-4 border-r">
              {/* Section 0: Search Bar */}
              <div className="mb-4">
                <input
                  id="search-input"
                  type="text"
                  placeholder="Search products..."
                  value={searchTerm}
                  onChange={(e) => setSearchTerm(e.target.value)}
                  onKeyDown={e => { 
                    if (e.key === 'Enter') e.target.blur(); 
                    if (e.key === 'ArrowUp') { 
                      e.target.blur(); 
                      setNavigationIndex(1); 
                    } 
                  }}
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
                  className="px-4 py-2 border rounded-lg"
                >
                  <option value="All">All Categories</option>
                  <option value="Medicine">Medicine</option>
                  <option value="Beverages">Beverages</option>
                  <option value="Snacks">Snacks</option>
                  <option value="Dairy">Dairy</option>
                </select>
              </div>

              {/* Section 1: Product Grid */}
              <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 overflow-y-auto flex-1 scrollbar-hide" style={{ maxHeight: '400px' }}>
                {filteredProducts.map((product, index) => (
                  <div
                    key={product.id}
                    className="flex justify-center"
                  >
                    <div
                      className={`border p-4 rounded-lg hover:shadow-md transition-shadow w-full max-w-sm ${
                        navigationIndex === 1 && selectedIndex === index ? 'ring-2 ring-blue-500 bg-blue-50' : ''
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
            <div className="md:w-[40%] p-4">
              {/* Cart Display */}
              <div className="bg-gray-50 rounded-lg p-4 mb-4">
                <div className="flex justify-between items-center mb-4">
                  <h2 className="text-xl font-bold">Cart ({cart.length} items)</h2>
                  {cart.length > 0 && (
                    <button
                      onClick={printReceipt}
                      className="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600"
                    >
                      Print Receipt
                    </button>
                  )}
                </div>
                {cart.length === 0 ? (
                  <div className="text-center py-8 text-gray-500">
                    <p>Your cart is empty</p>
                  </div>
                ) : (
                  <ul className="mb-4 max-h-40 overflow-y-auto">
                    {cart.map((item, idx) => (
                      <li key={item.product.id} className={`py-2 border-b ${checkoutFocusIndex === 'cart' && cartFocusIndex === idx ? 'ring-2 ring-blue-500 bg-blue-50' : ''}`}>
                        <div className="flex justify-between items-center">
                          <span className="font-medium">
                            {item.product.name} <span className="text-sm text-gray-500">x{item.quantity}</span>
                          </span>
                          <span className="flex items-center gap-2">
                            <button
                              className="px-2 py-1 bg-gray-200 rounded hover:bg-gray-300"
                              onClick={() => updateCartItemQuantity(item.product.id, item.quantity - 1)}
                            >
                              –
                            </button>
                            <span>₱{(item.product.price * item.quantity).toFixed(2)}</span>
                            <button
                              className="px-2 py-1 bg-gray-200 rounded hover:bg-gray-300"
                              onClick={() => updateCartItemQuantity(item.product.id, item.quantity + 1)}
                            >
                              +
                            </button>
                          </span>
                        </div>
                      </li>
                    ))}
                  </ul>
                )}
                {/* Cart Total */}
                <div className="flex justify-between items-center mt-4 mb-2 text-lg font-bold">
                  <span>Total:</span>
                  <span>₱{total.toFixed(2)}</span>
                </div>
                {/* Payment Form */}
                {cart.length > 0 && (
                  <div className="space-y-2 mt-4">
                    <input
                      ref={amountPaidRef}
                      type="text"
                      min="0"
                      placeholder="Amount Paid"
                      value={amountPaid}
                      onChange={e => setAmountPaid(e.target.value)}
                      onKeyDown={e => { if (e.key === 'Enter') { e.target.blur(); setCheckoutFocusIndex(1); } }}
                      onBlur={() => { justBlurredAmountPaid.current = true; }}
                      className={`w-full px-3 py-2 border rounded ${checkoutFocusIndex === 0 ? 'ring-2 ring-blue-500' : ''}`}
                    />
                    <div className="flex gap-2">
                      <button
                        ref={cashBtnRef}
                        type="button"
                        className={`flex-1 py-2 rounded ${paymentMethod === 'cash' ? 'bg-green-600 text-white' : 'bg-gray-200'} ${checkoutFocusIndex === 1 ? 'ring-2 ring-blue-500' : ''}`}
                        onClick={() => { setPaymentMethod('cash'); setShowRefInput(false); }}
                      >
                        Cash
                      </button>
                      <button
                        ref={gcashBtnRef}
                        type="button"
                        className={`flex-1 py-2 rounded ${paymentMethod === 'gcash' ? 'bg-blue-600 text-white' : 'bg-gray-200'} ${checkoutFocusIndex === 2 ? 'ring-2 ring-blue-500' : ''}`}
                        onClick={() => { setPaymentMethod('gcash'); setShowRefInput(true); setCheckoutFocusIndex(3); }}
                      >
                        GCash
                      </button>
                    </div>
                    {/* GCash Reference Number */}
                    {paymentMethod === 'gcash' && showRefInput && (
                      <input
                        ref={refNumRef}
                        type="text"
                        placeholder="GCash Reference Number"
                        value={referenceNumber}
                        onChange={e => setReferenceNumber(e.target.value)}
                        onKeyDown={e => { if (e.key === 'Enter') e.target.blur(); }}
                        className="w-full px-3 py-2 border rounded"
                      />
                    )}
                    {/* Change Display */}
                    {amountPaid && !isNaN(amountPaid) && (
                      <div className="flex justify-between items-center mt-2 text-md font-semibold">
                        <span>{change < 0 ? 'Needed:' : 'Change:'}</span>
                        <span className={change < 0 ? 'text-red-600' : 'text-green-600'}>
                          ₱{change.toFixed(2)}
                        </span>
                      </div>
                    )}
                  </div>
                )}
              </div>
              {/* Section 2: Checkout Button */}
              <button
                ref={checkoutBtnRef}
                id="checkout-button"
                onClick={handleCheckout}
                className={`w-full py-3 rounded-lg text-white font-semibold ${
                  checkoutFocusIndex === 4 ? 'bg-green-700 ring-2 ring-green-500' : 'bg-green-500 hover:bg-green-600'
                }`}
              >
                Checkout - ₱{total.toFixed(2)}
              </button>
            </div>
          </div>
        </div>
      </div>
      {showThankYouModal && (
        <div className="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-50">
          <div className="bg-white p-8 rounded shadow-lg text-center">
            <h2 className="text-2xl font-bold mb-4">Thank you for purchasing!</h2>
            <p className="text-gray-600">Your receipt is ready to print.</p>
            <button
              className="mt-4 px-6 py-2 bg-blue-600 text-white rounded hover:bg-blue-700"
              onClick={() => setShowThankYouModal(false)}
            >
              Close
            </button>
          </div>
        </div>
      )}
    </>
  );
}

              