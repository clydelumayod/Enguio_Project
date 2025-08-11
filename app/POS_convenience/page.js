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
  const [terminalName, setTerminalName] = useState(() => {
    if (typeof window !== 'undefined') {
      return localStorage.getItem('pos-terminal') || 'Convenience POS';
    }
    return 'Convenience POS';
  });
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
  const cartListRef = useRef(null);
  const cartItemRefs = useRef([]);
  const prevCartLenRef = useRef(0);
  const productListRef = useRef(null);
  const productItemRefs = useRef([]);
  const prevNavigationIndex = useRef(navigationIndex);
  const [cartFocusIndex, setCartFocusIndex] = useState(0);
  const justBlurredAmountPaid = useRef(false);
  const [showThankYouModal, setShowThankYouModal] = useState(false);
  const [showHistoryModal, setShowHistoryModal] = useState(false);
  const [salesHistory, setSalesHistory] = useState([]); // Persisted in localStorage
  const [historySelectedIndex, setHistorySelectedIndex] = useState(0);
  const [historyMode, setHistoryMode] = useState('sales'); // 'sales' | 'items'
  const [historyItemSelectedIndex, setHistoryItemSelectedIndex] = useState(0);
  const [showReturnQtyModal, setShowReturnQtyModal] = useState(false);
  const [returnModal, setReturnModal] = useState({ transactionId: null, productId: null, max: 0 });
  const [returnQtyInput, setReturnQtyInput] = useState('');
  const [showDiscountModal, setShowDiscountModal] = useState(false);
  const [discountType, setDiscountType] = useState(null); // string label from DB or 'PWD' | 'Senior' | null
  const [discountSelection, setDiscountSelection] = useState('PWD');
  const [discountAmount, setDiscountAmount] = useState(0);
  const [discountOptions, setDiscountOptions] = useState([]); // [{id, type, rate}]
  const [payableTotal, setPayableTotal] = useState(0);
  const [showAdjustmentModal, setShowAdjustmentModal] = useState(false);
  const [adjustmentProductId, setAdjustmentProductId] = useState(null);
  const [adjustmentQty, setAdjustmentQty] = useState('1');
  const [adjustmentReason, setAdjustmentReason] = useState('');

  const getDiscountTypesFromDb = () => (discountOptions?.length ? discountOptions.map(o => String(o.type)) : ['PWD', 'Senior Citizen']);

  const stepDiscountSelection = (step) => {
    const base = getDiscountTypesFromDb();
    const options = [...base, 'None'];
    const currentIndex = Math.max(0, options.indexOf(discountSelection));
    const nextIndex = (currentIndex + step + options.length) % options.length;
    setDiscountSelection(options[nextIndex]);
  };

  const getDiscountRatePercent = () => {
    if (!discountType) return 0;
    // Support synonyms (Senior -> Senior Citizen)
    const normalizedType = String(discountType).toLowerCase() === 'senior' ? 'senior citizen' : String(discountType).toLowerCase();
    const dbOption = discountOptions.find(o => String(o.type).toLowerCase() === normalizedType);
    let rate = 0;
    if (dbOption && Number.isFinite(dbOption.rate)) {
      rate = dbOption.rate;
      if (rate > 1) rate = rate / 100; // convert percentage 20 -> 0.20
    } else if (["pwd", "senior", "senior citizen"].includes(String(discountType).toLowerCase())) {
      rate = 0.20; // fallback default
    }
    return Math.round(rate * 100);
  };

  // Fetch discount options from backend
  useEffect(() => {
    const fetchDiscounts = async () => {
      try {
        const res = await fetch('http://localhost/Enguio_Project/Api/backend.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ action: 'get_discounts' })
        });
        const data = await res.json();
        if (data?.success && Array.isArray(data.data)) {
          const normalized = data.data.map(d => {
            const rawType = (d.discount_type ?? d.discountType ?? d.type ?? '').toString().trim();
            const tLower = rawType.toLowerCase();
            const finalType = !rawType ? 'Senior Citizen' : (tLower === 'senior' ? 'Senior Citizen' : rawType);
            const rateNum = Number(d.discount_rate ?? d.rate ?? 0);
            return { id: d.discount_id ?? d.id ?? finalType, type: finalType, rate: rateNum };
          });
          // Ensure we at least have PWD and Senior Citizen options if missing labels
          const types = new Set(normalized.map(o => o.type.toLowerCase()));
          if (!types.has('pwd')) normalized.push({ id: 'PWD', type: 'PWD', rate: 0.2 });
          if (!types.has('senior citizen')) normalized.push({ id: 'Senior Citizen', type: 'Senior Citizen', rate: 0.2 });
          setDiscountOptions(normalized);
        } else {
          setDiscountOptions([]);
        }
      } catch (e) {
        setDiscountOptions([]);
      }
    };
    fetchDiscounts();
  }, []);

  // Compute discount and payable total
  useEffect(() => {
    let rate = 0;
    if (discountType) {
      const dbOption = discountOptions.find(o => String(o.type).toLowerCase() === String(discountType).toLowerCase());
      if (dbOption && Number.isFinite(dbOption.rate) && dbOption.rate > 0 && dbOption.rate < 1.01) {
        rate = dbOption.rate; // assume decimal like 0.2; if 20 use conversion below
        if (rate > 1) rate = rate / 100; // support percentage stored as 20
      } else {
        // Fallback: 20% for PWD or Senior labels
        rate = 0.20;
      }
    }
    const computedDiscount = Number((total * rate).toFixed(2));
    const computedPayable = Math.max(0, Number((total - computedDiscount).toFixed(2)));
    setDiscountAmount(computedDiscount);
    setPayableTotal(computedPayable);
  }, [total, discountType, discountOptions]);

  // Compute change based on payable total
  useEffect(() => {
    const base = payableTotal;
    if (amountPaid && !isNaN(amountPaid)) {
      setChange(parseFloat(amountPaid) - base);
    } else {
      setChange(0);
    }
  }, [amountPaid, payableTotal]);

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

  // Auto-scroll the cart list to keep the focused item in view
  useEffect(() => {
    if (checkoutFocusIndex === 'cart' && cartItemRefs.current[cartFocusIndex]) {
      try {
        cartItemRefs.current[cartFocusIndex].scrollIntoView({ block: 'nearest' });
      } catch (_) {}
    }
  }, [checkoutFocusIndex, cartFocusIndex, cart.length]);

  // When items are added to cart, scroll to bottom so the new item is visible
  useEffect(() => {
    const prevLen = prevCartLenRef.current;
    if (cartListRef.current && cart.length > prevLen) {
      try {
        cartListRef.current.scrollTop = cartListRef.current.scrollHeight;
      } catch (_) {}
    }
    prevCartLenRef.current = cart.length;
  }, [cart.length]);

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
    // Load products from local storage if present; otherwise initialize with mockProducts
    const savedProducts = JSON.parse(localStorage.getItem('pos-products'));
    if (Array.isArray(savedProducts) && savedProducts.length > 0) {
      setProducts(savedProducts);
    } else {
    setProducts(mockProducts);
      localStorage.setItem('pos-products', JSON.stringify(mockProducts));
    }
    const savedCart = JSON.parse(localStorage.getItem('pos-cart'));
    if (savedCart) setCart(savedCart);
    const savedHistory = JSON.parse(localStorage.getItem('pos-sales-history')) || [];
    setSalesHistory(savedHistory);
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

  // Filter products based on search term and selected category, then sort A-Z
  const filteredProducts = products.filter(product =>
    product.name.toLowerCase().includes(searchTerm.toLowerCase()) &&
    (selectedCategory === 'All' || product.category === selectedCategory)
  );
  const sortedFilteredProducts = [...filteredProducts].sort((a, b) =>
    a.name.localeCompare(b.name, undefined, { sensitivity: 'base' })
  );

  // Update quantity input when navigating
  useEffect(() => {
    if (sortedFilteredProducts[selectedIndex]) {
      const product = sortedFilteredProducts[selectedIndex];
      if (!quantityInputs[product.id]) {
        setQuantityInputs(prev => ({ ...prev, [product.id]: 1 }));
      }
    }
    // Auto-scroll selected product fully into view (account for bottom action bar)
    if (navigationIndex === 1 && productItemRefs.current[selectedIndex] && productListRef.current) {
      try {
        const list = productListRef.current;
        const el = productItemRefs.current[selectedIndex];
        const bottomOverlayPx = 72; // approx. bottom bar height

        const elTop = el.offsetTop - list.offsetTop;
        const elBottom = elTop + el.offsetHeight;
        const visibleTop = list.scrollTop;
        const visibleBottom = list.scrollTop + list.clientHeight - bottomOverlayPx;

        if (elBottom > visibleBottom) {
          list.scrollTop = elBottom - list.clientHeight + bottomOverlayPx;
        } else if (elTop < visibleTop) {
          list.scrollTop = elTop;
        }
      } catch (_) {}
    }
  }, [selectedIndex, sortedFilteredProducts]);

  // Keyboard Navigation (Search, Products, Checkout)
  useEffect(() => {
    const handleKeyDown = (e) => {
      // Global toggle for history modal via Ctrl+H (works even when focused inside inputs)
      if (e.ctrlKey && (e.key === 'h' || e.key === 'H')) {
        e.preventDefault();
        setShowHistoryModal(prev => !prev);
        setHistoryMode('sales');
        setHistoryItemSelectedIndex(0);
        return;
      }

      // Global toggle for Discount modal via Ctrl+D
      if (e.ctrlKey && (e.key === 'd' || e.key === 'D')) {
        e.preventDefault();
        setDiscountSelection(discountType || 'PWD');
        setShowDiscountModal(prev => !prev);
        return;
      }

      // Global: open Product Adjustment modal via Ctrl+A for selected product
      if (e.ctrlKey && (e.key === 'a' || e.key === 'A')) {
        e.preventDefault();
        const product = filteredProducts[selectedIndex];
        if (product) {
          openAdjustmentModal(product.id);
        } else {
          alert('No product selected to adjust.');
        }
        return;
      }

      // If Return Quantity modal is open, capture keys
      if (showReturnQtyModal) {
        if (e.key === 'Escape') {
          e.preventDefault();
          setShowReturnQtyModal(false);
          return;
        }
        if (e.key === 'ArrowUp') {
          e.preventDefault();
          const max = Number(returnModal.max || 0);
          const current = Math.min(Math.max(1, Number(returnQtyInput || 0)), max) || 1;
          const next = Math.min(max, current + 1);
          setReturnQtyInput(String(next));
          return;
        }
        if (e.key === 'ArrowDown') {
          e.preventDefault();
          const max = Number(returnModal.max || 0);
          const current = Math.min(Math.max(1, Number(returnQtyInput || 0)), max) || 1;
          const next = Math.max(1, current - 1);
          setReturnQtyInput(String(next));
          return;
        }
        if (e.key === 'Enter') {
          e.preventDefault();
          const qty = Number(returnQtyInput);
          if (Number.isFinite(qty) && qty >= 1 && qty <= Number(returnModal.max || 0)) {
            handleReturnItem(returnModal.transactionId, returnModal.productId, qty);
            setShowReturnQtyModal(false);
          }
          return;
        }
        return; // block other shortcuts while qty modal open
      }

      // Handle keys inside Product Adjustment Modal
      if (showAdjustmentModal) {
        if (e.key === 'Escape') {
          e.preventDefault();
          setShowAdjustmentModal(false);
          return;
        }
        if (e.key === 'Enter') {
          e.preventDefault();
          confirmAdjustment();
          return;
        }
        return; // block other shortcuts while adjustment modal open
      }

      // Handle keys inside Sales History Modal
      if (showHistoryModal) {
        if (["ArrowDown", "s", "S"].includes(e.key)) {
          e.preventDefault();
          if (historyMode === 'sales') {
            setHistorySelectedIndex(prev => (prev + 1) % Math.max(salesHistory.length, 1));
          } else {
            const itemsLen = salesHistory[historySelectedIndex]?.items?.length || 0;
            if (itemsLen > 0) setHistoryItemSelectedIndex(prev => (prev + 1) % itemsLen);
          }
          return;
        }
        if (["ArrowUp", "w", "W"].includes(e.key)) {
          e.preventDefault();
          if (historyMode === 'sales') {
            setHistorySelectedIndex(prev => (prev - 1 + Math.max(salesHistory.length, 1)) % Math.max(salesHistory.length, 1));
          } else {
            const itemsLen = salesHistory[historySelectedIndex]?.items?.length || 0;
            if (itemsLen > 0) setHistoryItemSelectedIndex(prev => (prev - 1 + itemsLen) % itemsLen);
          }
          return;
        }
        if (e.key === 'Enter') {
          e.preventDefault();
          if (historyMode === 'sales') {
            setHistoryMode('items');
            setHistoryItemSelectedIndex(0);
          } else {
            // Return selected item
            const sale = salesHistory[historySelectedIndex];
            const item = sale?.items?.[historyItemSelectedIndex];
            if (sale && item) openReturnQtyModal(sale.transactionId, item.id);
          }
          return;
        }
        if (e.key === 'Escape') {
          e.preventDefault();
          if (historyMode === 'items') {
            setHistoryMode('sales');
          } else {
            setShowHistoryModal(false);
          }
          return;
        }
        return; // Block other shortcuts while modal is open
      }

      // Handle keys inside Discount Modal
      if (showDiscountModal) {
        if (e.key === 'Escape') {
          e.preventDefault();
          setShowDiscountModal(false);
          return;
        }
        // Up/Down cycles options too (in addition to Left/Right)
        if (["ArrowUp", "w", "W"].includes(e.key)) {
          e.preventDefault();
          const options = [...getDiscountTypesFromDb(), 'None'];
          const idx = options.indexOf(discountSelection);
          const next = (idx - 1 + options.length) % options.length;
          setDiscountSelection(options[next]);
          return;
        }
        if (["ArrowDown", "s", "S"].includes(e.key)) {
          e.preventDefault();
          const options = [...getDiscountTypesFromDb(), 'None'];
          const idx = options.indexOf(discountSelection);
          const next = (idx + 1) % options.length;
          setDiscountSelection(options[next]);
          return;
        }
        if (e.key === 'Enter') {
          e.preventDefault();
          if (discountSelection === 'None') {
            setDiscountType(null);
          } else {
            setDiscountType(discountSelection);
          }
          setShowDiscountModal(false);
          return;
        }
        if (["ArrowLeft", "a", "A", "ArrowRight", "d", "D"].includes(e.key)) {
          e.preventDefault();
          const options = [...getDiscountTypesFromDb(), 'None'];
          const idx = options.indexOf(discountSelection);
          const next = (idx + (e.key === 'ArrowLeft' || e.key === 'a' || e.key === 'A' ? -1 : 1) + options.length) % options.length;
          setDiscountSelection(options[next]);
          return;
        }
        return; // block other shortcuts while discount modal open
      }

      // Prevent navigation if user is typing in input
      if (
        ['INPUT', 'SELECT', 'TEXTAREA'].includes(document.activeElement.tagName)
      ) return;

      const cols = 1; // List view: single column for product list
      const maxIndex = sortedFilteredProducts.length - 1;
      const spacerIndex = sortedFilteredProducts.length; // virtual last row

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
            // Jump directly to checkout amount input
              setNavigationIndex(2);
            setCheckoutFocusIndex(0);
          }
          break;

        case 'Enter':
          e.preventDefault();

          if (navigationIndex === 0) {
            // Focus search bar
            document.getElementById('search-input')?.focus();
          } else if (navigationIndex === 1 && sortedFilteredProducts[selectedIndex]) {
            // Add selected product to cart
            const product = sortedFilteredProducts[selectedIndex];
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
  }, [navigationIndex, selectedIndex, filteredProducts, quantityInputs, cart, cartFocusIndex, showHistoryModal, salesHistory, historySelectedIndex, showDiscountModal, discountSelection, discountType, payableTotal]);

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

  const printReceipt = async () => {
    // Get current date and time
    const now = new Date();
    const dateStr = now.toLocaleDateString();
    const timeStr = now.toLocaleTimeString();
    const transactionId = `TXN${now.getTime().toString().slice(-6)}`;

    // Prepare receipt data
    const receiptData = {
      storeName: "Enguios Pharmacy & Convenience Store",
      date: dateStr,
      time: timeStr,
      transactionId: transactionId,
      cashier: (typeof window !== 'undefined' && (localStorage.getItem('pos-cashier') || localStorage.getItem('currentUser') || localStorage.getItem('user') || 'Admin')),
      terminalName,
      items: cart.map(item => ({
        name: item.product.name,
        quantity: item.quantity,
        price: item.product.price,
        total: item.product.price * item.quantity
      })),
      subtotal: total,
      discountType: discountType || null,
      discountAmount: discountAmount,
      grandTotal: payableTotal,
      paymentMethod: paymentMethod.toUpperCase(),
      amountPaid: parseFloat(amountPaid),
      change: change,
      gcashRef: paymentMethod === 'gcash' ? referenceNumber : null
    };

    try {
      console.log('Sending receipt data:', receiptData);
      
      // Call the PHP backend directly since Next.js API is broken
              const response = await fetch('http://localhost/Enguio_Project/Api/print-receipt-fixed-width.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify(receiptData)
      });

      if (!response.ok) {
        const errorText = await response.text();
        console.error('HTTP Error:', response.status, errorText);
        throw new Error(`HTTP error! status: ${response.status}: ${errorText}`);
      }

      const result = await response.json();
      console.log('Print result:', result);
      
      if (!result.success) {
        throw new Error(result.message || 'Failed to print receipt');
      }
      
      // Show success message
      console.log('Receipt printed successfully:', result.data?.transactionId || transactionId);
      // Save sale to DB (header + details)
      try {
        const saveRes = await fetch('http://localhost/Enguio_Project/Api/backend.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            action: 'save_pos_sale',
            transactionId,
            totalAmount: payableTotal,
            referenceNumber: paymentMethod === 'gcash' ? referenceNumber : null,
            terminalName,
            items: cart.map(it => ({ product_id: it.product.id, quantity: it.quantity, price: it.product.price }))
          })
        });
        const saveJson = await saveRes.json();
        console.log('Save sale result:', saveJson);
      } catch (e) {
        console.warn('Failed to save sale to DB:', e);
      }
      return { success: true, message: 'Receipt printed successfully', transactionId };
      
    } catch (error) {
      console.error('Print error:', error);
      // Return error details for better debugging
      return { success: false, message: error.message, transactionId };
    }
  };

  // Save sale to history (localStorage)
  const saveSaleToHistory = (saleRecord) => {
    try {
      const existing = JSON.parse(localStorage.getItem('pos-sales-history')) || [];
      const updated = [saleRecord, ...existing].slice(0, 200); // keep latest 200
      localStorage.setItem('pos-sales-history', JSON.stringify(updated));
      setSalesHistory(updated);
    } catch (err) {
      console.error('Failed to save sale history:', err);
    }
  };

  // Mark sale as returned by transactionId
  const handleReturnSale = (transactionId) => {
    if (!transactionId) return;
    const confirmReturn = window.confirm('Mark this sale as returned?');
    if (!confirmReturn) return;
    setSalesHistory(prev => {
      const updated = prev.map(sale => (
        sale.transactionId === transactionId
          ? { ...sale, status: 'returned', returnedAt: new Date().toISOString() }
          : sale
      ));
      localStorage.setItem('pos-sales-history', JSON.stringify(updated));
      return updated;
    });
    setShowHistoryModal(false);
  };

  // Adjust product stock and persist to localStorage
  const adjustProductStock = (productId, delta) => {
    setProducts(prevProducts => {
      const updated = prevProducts.map(p => p.id === productId ? { ...p, quantity: Math.max(0, (p.quantity || 0) + delta) } : p);
      localStorage.setItem('pos-products', JSON.stringify(updated));
      return updated;
    });
  };

  // Open modal to return item quantity
  const openReturnQtyModal = (transactionId, productId) => {
    if (!transactionId || !productId) return;
    const sale = salesHistory.find(s => s.transactionId === transactionId);
    if (!sale) return;
    const item = sale.items?.find(i => i.id === productId);
    if (!item) return;
    const alreadyReturned = Number(item.returnedQuantity || 0);
    const maxReturnable = Math.max(0, Number(item.quantity || 0) - alreadyReturned);
    if (maxReturnable <= 0) {
      alert('Nothing left to return for this item.');
      return;
    }
    setReturnModal({ transactionId, productId, max: maxReturnable });
    setReturnQtyInput(String(maxReturnable));
    setShowReturnQtyModal(true);
  };

  // Return a specific item from a sale (by transactionId and productId) with explicit quantity
  const handleReturnItem = (transactionId, productId, qty) => {
    if (!transactionId || !productId) return;
    const sale = salesHistory.find(s => s.transactionId === transactionId);
    if (!sale) return;
    const item = sale.items?.find(i => i.id === productId);
    if (!item) return;
    const alreadyReturned = Number(item.returnedQuantity || 0);
    const maxReturnable = Math.max(0, Number(item.quantity || 0) - alreadyReturned);
    const quantityToReturn = Number(qty);
    if (!Number.isFinite(quantityToReturn) || quantityToReturn <= 0 || quantityToReturn > maxReturnable) {
      alert('Invalid quantity.');
      return;
    }
    // Update history record
    setSalesHistory(prev => {
      const updated = prev.map(saleRec => {
        if (saleRec.transactionId !== transactionId) return saleRec;
        const updatedItems = saleRec.items.map(it => it.id === productId ? { ...it, returnedQuantity: (Number(it.returnedQuantity || 0) + qty) } : it);
        const allReturned = updatedItems.every(it => Number(it.returnedQuantity || 0) >= Number(it.quantity || 0));
        const newStatus = allReturned ? 'returned' : 'partially-returned';
        return { ...saleRec, items: updatedItems, status: newStatus, updatedAt: new Date().toISOString(), returnedAt: allReturned ? new Date().toISOString() : saleRec.returnedAt };
      });
      localStorage.setItem('pos-sales-history', JSON.stringify(updated));
      return updated;
    });
    // Restock the product
    adjustProductStock(productId, quantityToReturn);
  };

  // Open adjustment modal for a given product
  const openAdjustmentModal = (productId) => {
    setAdjustmentProductId(productId);
    setAdjustmentQty('1');
    setAdjustmentReason('');
    setShowAdjustmentModal(true);
  };

  // Confirm adjustment: subtract damaged qty from stock with a reason
  const confirmAdjustment = () => {
    if (!adjustmentProductId) return;
    const product = products.find(p => p.id === adjustmentProductId);
    if (!product) return;
    const currentQty = Number(product.quantity || 0);
    const qty = Number(adjustmentQty);
    if (!Number.isFinite(qty) || qty <= 0) {
      alert('Enter a valid damaged quantity.');
      return;
    }
    if (!adjustmentReason.trim()) {
      alert('Please provide a reason for the adjustment.');
      return;
    }
    if (qty > currentQty) {
      alert('Damaged quantity cannot exceed current stock.');
      return;
    }
    adjustProductStock(adjustmentProductId, -qty);
    setShowAdjustmentModal(false);
  };

  const handleCheckout = async () => {
    if (cart.length === 0) return;
    
    // Validate payment
    if (!amountPaid || isNaN(amountPaid) || parseFloat(amountPaid) < payableTotal) {
      alert('Please enter a valid amount that covers the total cost.');
      return;
    }
    
    if (paymentMethod === 'gcash' && !referenceNumber.trim()) {
      alert('Please enter GCash reference number.');
      return;
    }
    
    // Try to print receipt (but don't block checkout if it fails)
    const printResult = await printReceipt();

    // Persist this sale in local history regardless of print success
    const saleRecord = {
      transactionId: printResult?.transactionId,
      date: new Date().toLocaleDateString(),
      time: new Date().toLocaleTimeString(),
      items: cart.map(item => ({
        id: item.product.id,
        name: item.product.name,
        quantity: item.quantity,
        price: item.product.price,
        total: item.product.price * item.quantity,
        returnedQuantity: 0,
      })),
      subtotal: total,
      discountType: discountType || null,
      discountAmount: discountAmount,
      grandTotal: payableTotal,
      paymentMethod: paymentMethod?.toUpperCase?.() || '',
      amountPaid: parseFloat(amountPaid),
      change: change,
      gcashRef: paymentMethod === 'gcash' ? referenceNumber : null,
      printStatus: printResult?.success ? 'success' : 'failed',
      status: 'completed',
      createdAt: new Date().toISOString(),
    };
    saveSaleToHistory(saleRecord);

    // Decrement product stock for each item sold
    cart.forEach(item => {
      adjustProductStock(item.product.id, -Number(item.quantity || 0));
    });
    
    // Always proceed with checkout regardless of print success
    // Clear cart and reset state
    setCart([]);
    localStorage.removeItem('pos-cart');
    setAmountPaid('');
    setReferenceNumber('');
    setPaymentMethod('');
    setShowRefInput(false);
    setShowThankYouModal(true);
    
    // Show appropriate message based on print success
    if (printResult.success) {
      console.log('Transaction completed successfully with receipt processed.');
    } else {
      console.log('Transaction completed successfully but receipt processing failed:', printResult.message);
      // Optionally show a warning to the user about printing failure
      setTimeout(() => {
        alert(`Transaction completed but printing failed: ${printResult.message}\n\nCheck the receipts folder for saved receipt.`);
      }, 2500); // Show after thank you modal
    }
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
      <div className="min-h-screen w-full bg-gray-100 pb-20">
        <div className="max-w-9xl mx-auto bg-white shadow-lg min-h-screen">
          {/* Header */}
          <div className="bg-green-500 p-4 text-white">
           <img src="assets/enguio_logo.png" alt="logo" className="w-20 h-20" />
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
                    navigationIndex === 0 ? 'ring-2 ring-green-500' : ''
                  }`}
                />
              </div>

              {/* Category Dropdown */}
              <div className="mb-4">
                <div className="mb-2 flex items-center gap-2">
                  <label className="text-sm text-gray-600">Terminal:</label>
                  <input
                    type="text"
                    value={terminalName}
                    onChange={(e) => { setTerminalName(e.target.value); if (typeof window !== 'undefined') localStorage.setItem('pos-terminal', e.target.value); }}
                    className="px-2 py-1 border rounded w-64"
                    placeholder="e.g., Pharmacy POS"
                  />
                </div>
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

              {/* Section 1: Product List (A-Z) */}
              <div ref={productListRef} className="overflow-y-auto flex-1 scrollbar-hide" style={{ maxHeight: '400px' }}>
                {/* Table-like header */}
                <div className="grid grid-cols-[minmax(0,1fr)_100px_100px_140px] gap-2 px-3 py-2 text-xs font-semibold text-gray-600 bg-gray-50 sticky top-0 z-10 border-b">
                  <div>Product</div>
                  <div className="text-right">Stock</div>
                  <div className="text-right">Price</div>
                  <div className="text-right">Qty / Action</div>
                </div>
                <ul className="divide-y divide-gray-200">
                  {sortedFilteredProducts.map((product, index) => (
                    <li
                      ref={el => productItemRefs.current[index] = el}
                    key={product.id}
                      className={`grid grid-cols-[minmax(0,1fr)_100px_100px_140px] items-center gap-2 px-3 py-2 ${navigationIndex === 1 && selectedIndex === index ? 'ring-2 ring-green-500 bg-green-50' : ''}`}
                    >
                      <div className="min-w-0 truncate font-medium">{product.name}</div>
                      <div className="text-right text-gray-600">{product.quantity}</div>
                      <div className="text-right text-green-600 font-semibold">₱{product.price.toFixed(2)}</div>
                      <div className="flex items-center justify-end gap-3">
                        <input
                          type="number"
                          min="1"
                          max={product.quantity}
                          value={quantityInputs[product.id] || 1}
                          readOnly
                          className="w-16 px-2 py-1 border rounded"
                        />
                        <button
                          onClick={() => addToCart(product, quantityInputs[product.id] || 1)}
                          className="bg-green-500 text-white px-3 py-1 rounded hover:bg-blue-600"
                        >
                          Add
                        </button>
                      </div>
                    </li>
                ))}
                  {/* Toggleable Spacer item to allow last product to scroll above the bottom bar */}
                  <li
                    ref={el => productItemRefs.current[sortedFilteredProducts.length] = el}
                    key="spacer-row"
                    className={`px-3 py-4 text-center select-none ${navigationIndex === 1 && selectedIndex === sortedFilteredProducts.length ? 'ring-2 ring-green-500 bg-green-50' : ''}`}
                  >
                    <div className="py-4 text-xs text-gray-400">More products available soon…</div>
                  </li>
                </ul>
              </div>
            </div>

            {/* Right Side - Cart & Checkout */}
            <div className="md:w-[40%] p-4">
              {/* Cart Display */}
              <div className="bg-gray-50 rounded-lg p-4 mb-4">
                <div className="flex justify-between items-center mb-4">
                  <h2 className="text-xl font-bold">Cart ({cart.length} items)</h2>
                </div>
                {cart.length === 0 ? (
                  <div className="text-center py-8 text-gray-500">
                    <p>Your cart is empty</p>
                  </div>
                ) : (
                  <ul ref={cartListRef} className="mb-4 max-h-40 overflow-y-auto">
                    {cart.map((item, idx) => (
                      <li ref={el => cartItemRefs.current[idx] = el} key={item.product.id} className={`py-2 border-b ${checkoutFocusIndex === 'cart' && cartFocusIndex === idx ? 'ring-2 ring-blue-500 bg-blue-50' : ''}`}>
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
                {/* Cart Total & Discount */}
                <div className="flex flex-col gap-1 mt-4 mb-2">
                  <div className="flex justify-between items-center text-base font-semibold">
                    <span>Subtotal:</span>
                    <span>₱{total.toFixed(2)}</span>
                  </div>
                  {discountType && (
                    <div className="flex justify-between items-center text-sm text-green-700">
                      <span>Discount ({discountType} {getDiscountRatePercent()}%):</span>
                      <span>-₱{discountAmount.toFixed(2)}</span>
                    </div>
                  )}
                  <div className="flex justify-between items-center text-lg font-bold">
                    <span>Payable:</span>
                    <span>₱{payableTotal.toFixed(2)}</span>
                  </div>
                </div>
                {/* Payment Form */}
                {cart.length > 0 && (
                  <div className="space-y-2 mt-4">
                    <div className="flex gap-2">
                      <button
                        type="button"
                        className="px-3 py-2 rounded bg-purple-600 text-white"
                        onClick={() => { setDiscountSelection(discountType || 'PWD'); setShowDiscountModal(true); }}
                      >
                        Discount (Ctrl+D)
                      </button>
                    </div>
                    <input
                      ref={amountPaidRef}
                      type="text"
                      min="0"
                      placeholder="Amount Paid"
                      value={amountPaid}
                      onChange={e => setAmountPaid(e.target.value)}
                      onKeyDown={e => { if (e.key === 'Enter') { e.target.blur(); setCheckoutFocusIndex(1); } }}
                      onBlur={() => { justBlurredAmountPaid.current = true; }}
                      className={`w-full px-3 py-2 border rounded ${checkoutFocusIndex === 0 ? 'ring-2 ring-green-500' : ''}`}
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
                Checkout - ₱{payableTotal.toFixed(2)}
              </button>
            </div>
          </div>
        </div>
      </div>
      {/* Bottom action bar */}
      <div className="fixed bottom-0 left-0 right-0 bg-white border-t shadow z-40">
        <div className="max-w-9xl mx-auto px-3 py-2 flex gap-2 flex-wrap">
          <button
            type="button"
            className="px-3 py-2 rounded bg-gray-800 text-white hover:bg-gray-700"
            onClick={() => { setShowHistoryModal(true); setHistoryMode('sales'); }}
          >
            History (Ctrl+H)
          </button>
          <button
            type="button"
            className="px-3 py-2 rounded bg-purple-600 text-white hover:bg-purple-700"
            onClick={() => { setDiscountSelection(discountType || 'PWD'); setShowDiscountModal(true); }}
          >
            Discount (Ctrl+D)
          </button>
          <button
            type="button"
            className="px-3 py-2 rounded bg-orange-600 text-white hover:bg-orange-700"
            onClick={() => { const p = (sortedFilteredProducts || [])[selectedIndex]; if (p) { openAdjustmentModal(p.id); } else { alert('Select a product to adjust.'); } }}
          >
            Adjustment (Ctrl+A)
          </button>
          <button
            type="button"
            className="px-3 py-2 rounded bg-blue-600 text-white hover:bg-blue-700"
            onClick={() => { setShowHistoryModal(true); setHistoryMode('sales'); }}
          >
            Returns
          </button>
        </div>
      </div>
      {showHistoryModal && (
        <div className="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-50">
          <div className="bg-white rounded-xl shadow-lg w-full max-w-4xl max-h-[85vh] overflow-hidden">
            <div className="flex items-center justify-between px-4 py-3 border-b">
              <h3 className="text-xl font-bold">Sales History</h3>
              <div className="text-sm text-gray-500">Ctrl+H to close</div>
            </div>
            <div className="flex">
              <div className="w-1/2 border-r max-h-[70vh] overflow-y-auto">
                {salesHistory.length === 0 ? (
                  <div className="p-6 text-gray-500 text-center">No sales yet</div>
                ) : (
                  <ul>
                    {salesHistory.map((sale, idx) => (
                      <li
                        key={`${sale.transactionId}-${idx}`}
                        className={`px-4 py-3 border-b cursor-pointer ${historySelectedIndex === idx && historyMode === 'sales' ? 'bg-blue-50 ring-2 ring-blue-300' : ''}`}
                      >
                        <div className="flex justify-between">
                          <div>
                            <div className="font-semibold">{sale.transactionId}</div>
                            <div className="text-xs text-gray-500">{sale.date} {sale.time}</div>
                          </div>
                          <div className="text-right">
                            <div className="font-semibold">₱{Number(sale.subtotal || 0).toFixed(2)}</div>
                            <div className={`text-xs ${sale.status === 'returned' ? 'text-red-600' : sale.status === 'partially-returned' ? 'text-orange-600' : 'text-green-600'}`}>{sale.status || 'completed'}</div>
                          </div>
                        </div>
                        <div className="text-xs text-gray-500 mt-1">{sale.paymentMethod}</div>
                      </li>
                    ))}
                  </ul>
                )}
              </div>
              <div className="w-1/2 max-h-[70vh] overflow-y-auto">
                {salesHistory[historySelectedIndex] ? (
                  <div className="p-4">
                    <div className="mb-2">
                      <div className="text-sm text-gray-600">Transaction</div>
                      <div className="font-semibold">{salesHistory[historySelectedIndex].transactionId}</div>
                    </div>
                    <div className="mb-2 grid grid-cols-3 gap-2 text-sm text-gray-700">
                      <div>Date: {salesHistory[historySelectedIndex].date}</div>
                      <div>Time: {salesHistory[historySelectedIndex].time}</div>
                      <div>Payment: {salesHistory[historySelectedIndex].paymentMethod}</div>
                    </div>
                    <div className="border rounded-lg">
                      <div className="px-3 py-2 font-semibold bg-gray-50">Items</div>
                      <ul>
                        {salesHistory[historySelectedIndex].items?.map((it, i) => {
                          const returnedQty = Number(it.returnedQuantity || 0);
                          const remaining = Math.max(0, Number(it.quantity || 0) - returnedQty);
                          return (
                            <li key={i} className={`px-3 py-2 border-t text-sm flex items-center justify-between ${historyMode === 'items' && historyItemSelectedIndex === i ? 'bg-blue-50 ring-2 ring-blue-300' : ''}`}>
                              <div>
                                <div className="font-medium">{it.name} x{it.quantity}</div>
                                <div className="text-xs text-gray-500">Returned: {returnedQty} | Remaining: {remaining}</div>
                              </div>
                              <div className="flex items-center gap-3">
                                <span>₱{Number(it.total || (it.price * it.quantity)).toFixed(2)}</span>
                                <button
                                  className="px-3 py-1 rounded bg-red-600 text-white disabled:opacity-50"
                                  disabled={remaining <= 0}
                                  onClick={() => openReturnQtyModal(salesHistory[historySelectedIndex].transactionId, it.id)}
                                >
                                  Return
                                </button>
                              </div>
                            </li>
                          );
                        })}
                      </ul>
                    </div>
                    <div className="flex justify-between items-center mt-3 font-semibold">
                      <span>Total</span>
                      <span>₱{Number(salesHistory[historySelectedIndex].subtotal || 0).toFixed(2)}</span>
                    </div>
                    <div className="mt-4 flex gap-2">
                      <button
                        className="px-4 py-2 rounded bg-gray-200"
                        onClick={() => setShowHistoryModal(false)}
                      >
                        Close
                      </button>
                    </div>
                    <div className="text-xs text-gray-500 mt-2">Use Up/Down to navigate. Enter: select sale or return selected item. ESC: go back/close.</div>
                  </div>
                ) : (
                  <div className="p-6 text-gray-500">Select a sale to view details</div>
                )}
              </div>
            </div>
          </div>
        </div>
      )}
      {showDiscountModal && (
        <div className="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-[65]">
          <div className="bg-white p-6 rounded-xl shadow-lg w-full max-w-md">
            <div className="flex items-center justify-between mb-3">
              <h4 className="text-lg font-bold">Apply Discount</h4>
                  <div className="flex items-center gap-2">
                <button
                  type="button"
                  className="px-2 py-1 rounded border bg-white hover:bg-gray-100"
                  aria-label="Previous option (Left Arrow)"
                  onClick={() => stepDiscountSelection(-1)}
                >
                  ◀
                </button>
                <button
                  type="button"
                  className="px-2 py-1 rounded border bg-white hover:bg-gray-100"
                  aria-label="Next option (Right Arrow)"
                  onClick={() => stepDiscountSelection(1)}
                >
                  ▶
                </button>
              </div>
            </div>
            <div className="space-y-2 mb-4">
              {discountOptions.length > 0 ? (
                discountOptions.map(opt => (
                      <button
                        key={opt.id}
                        className={`w-full py-2 rounded border ${discountSelection === opt.type ? 'bg-purple-100 border-purple-400' : 'bg-white'}`}
                        onClick={() => setDiscountSelection(opt.type)}
                      >
                    {opt.type || (String(opt.type).toLowerCase() === 'senior' ? 'Senior Citizen' : opt.type)} - {((opt.rate > 1 ? opt.rate : opt.rate * 100) || 20).toFixed(0)}%
                      </button>
                    ))
                  ) : (
                    <>
              <button
                className={`w-full py-2 rounded border ${discountSelection === 'PWD' ? 'bg-purple-100 border-purple-400' : 'bg-white'}`}
                onClick={() => setDiscountSelection('PWD')}
              >
                PWD - 20%
              </button>
              <button
                    className={`w-full py-2 rounded border ${discountSelection === 'Senior Citizen' ? 'bg-purple-100 border-purple-400' : 'bg-white'}`}
                    onClick={() => setDiscountSelection('Senior Citizen')}
              >
                Senior Citizen - 20%
              </button>
                    </>
                  )}
              <button
                className={`w-full py-2 rounded border ${discountSelection === 'None' ? 'bg-purple-100 border-purple-400' : 'bg-white'}`}
                onClick={() => setDiscountSelection('None')}
              >
                Remove Discount
              </button>
            </div>
            <div className="flex gap-2 justify-end">
              <button className="px-4 py-2 rounded bg-gray-200" onClick={() => setShowDiscountModal(false)}>Cancel (Esc)</button>
              <button
                className="px-4 py-2 rounded bg-green-600 text-white"
                onClick={() => { if (discountSelection === 'None') setDiscountType(null); else setDiscountType(discountSelection); setShowDiscountModal(false); }}
              >
                Apply (Enter)
              </button>
            </div>
            <div className="text-xs text-gray-500 mt-2">Ctrl+D to open. Use Left/Right to switch options. Enter to apply. Esc to close.</div>
          </div>
        </div>
      )}
      {showReturnQtyModal && (
        <div className="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-[60]">
          <div className="bg-white p-6 rounded-xl shadow-lg w-full max-w-md">
            <h4 className="text-lg font-bold mb-2">Return Quantity</h4>
            <p className="text-sm text-gray-600 mb-4">Enter the quantity to return. Max {returnModal.max}.</p>
            <input
              type="number"
              min={1}
              max={Number(returnModal.max || 0)}
              value={returnQtyInput}
              onChange={(e) => {
                const max = Number(returnModal.max || 0);
                const v = e.target.value;
                if (v === '') { setReturnQtyInput(''); return; }
                const n = Number(v);
                if (!Number.isFinite(n)) return;
                const clamped = Math.max(1, Math.min(max, n));
                setReturnQtyInput(String(clamped));
              }}
              className="w-full px-3 py-2 border rounded mb-4"
            />
            <div className="flex gap-2 justify-end">
              <button className="px-4 py-2 rounded bg-gray-200" onClick={() => setShowReturnQtyModal(false)}>Cancel</button>
              <button
                className="px-4 py-2 rounded bg-orange-600 text-white"
                onClick={() => {
                  setReturnQtyInput(String(returnModal.max));
                  handleReturnItem(returnModal.transactionId, returnModal.productId, Number(returnModal.max || 0));
                  setShowReturnQtyModal(false);
                }}
              >
                Return All
              </button>
              <button
                className="px-4 py-2 rounded bg-green-600 text-white disabled:opacity-50"
                disabled={!Number.isFinite(Number(returnQtyInput)) || Number(returnQtyInput) < 1 || Number(returnQtyInput) > Number(returnModal.max || 0)}
                onClick={() => {
                  const qty = Number(returnQtyInput);
                  if (Number.isFinite(qty) && qty >= 1 && qty <= Number(returnModal.max || 0)) {
                    handleReturnItem(returnModal.transactionId, returnModal.productId, qty);
                    setShowReturnQtyModal(false);
                  }
                }}
              >
                Confirm
              </button>
            </div>
          </div>
        </div>
      )}
      {showAdjustmentModal && (
        <div className="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-[70]">
          <div className="bg-white p-6 rounded-xl shadow-lg w-full max-w-md">
            <h4 className="text-lg font-bold mb-2">Product Adjustment (Damaged)</h4>
            {(() => {
              const product = products.find(p => p.id === adjustmentProductId);
              const currentQty = Number(product?.quantity || 0);
              return (
                <div>
                  <div className="mb-2">
                    <div className="text-sm text-gray-600">Product</div>
                    <div className="font-semibold">{product?.name || '—'}</div>
                  </div>
                  <div className="mb-3 text-sm text-gray-700">Current Stock: {currentQty}</div>
                  <div className="mb-3">
                    <label className="block text-sm font-medium text-gray-700 mb-1">Damaged Quantity</label>
                    <input
                      type="number"
                      min={1}
                      max={currentQty}
                      value={adjustmentQty}
                      onChange={e => setAdjustmentQty(e.target.value)}
                      className="w-full px-3 py-2 border rounded"
                    />
                  </div>
                  <div className="mb-3">
                    <label className="block text-sm font-medium text-gray-700 mb-1">Reason</label>
                    <textarea
                      rows={3}
                      value={adjustmentReason}
                      onChange={e => setAdjustmentReason(e.target.value)}
                      placeholder="e.g., Damaged during transport"
                      className="w-full px-3 py-2 border rounded"
                    />
                  </div>
                  <div className="flex gap-2 justify-end">
                    <button className="px-4 py-2 rounded bg-gray-200" onClick={() => setShowAdjustmentModal(false)}>Cancel (Esc)</button>
                    <button className="px-4 py-2 rounded bg-green-600 text-white" onClick={confirmAdjustment}>Confirm</button>
                  </div>
                  <div className="text-xs text-gray-500 mt-2">Ctrl+A from product list to open. Enter to confirm. Esc to close.</div>
                </div>
              );
            })()}
          </div>
        </div>
      )}
      {showThankYouModal && (
        <div className="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-50">
          <div className="bg-white p-8 rounded-xl shadow-lg text-center">
            <h2 className="text-3xl font-bold mb-4 text-green-600">Thank you for purchasing!</h2>
            <p className="text-gray-600 text-lg">Transaction completed successfully.</p>
            <p className="text-sm text-gray-500 mt-2">Receipt data sent to printer successfully.</p>
            <p className="text-xs text-orange-500 mt-2 font-semibold">📋 If paper doesnt feed automatically, press the manual feed button on your printer.</p>
            <p className="text-xs text-gray-400 mt-1">Receipt is also saved in the receipts folder.</p>
            <button
              className="mt-6 px-8 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-semibold"
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

               