const express = require('express');
const bodyParser = require('body-parser');
const printer = require('printer');

const app = express();
const PORT = 4000;

app.use(bodyParser.json());

// Debug: List all available printers on startup
console.log('Available printers:', printer.getPrinters().map(p => p.name));

app.post('/print', (req, res) => {
  const data = req.body;

  if (!data || !data.items || !Array.isArray(data.items)) {
    return res.status(400).json({ success: false, message: 'Invalid receipt data' });
  }

  // Format the receipt as plain text
  const receiptLines = [
    '      Enguios Pharmacy & Convenience Store',
    '                Receipt',
    '----------------------------------------',
    `Date: ${data.date}`,
    `Time: ${data.time}`,
    `Transaction #: ${data.transactionId}`,
    '----------------------------------------',
    ...data.items.map(item =>
      `${item.name}\n  ${item.quantity} x ₱${item.price.toFixed(2)} = ₱${(item.price * item.quantity).toFixed(2)}`
    ),
    '----------------------------------------',
    `Subtotal: ₱${data.subtotal.toFixed(2)}`,
    `Payment Method: ${data.paymentMethod}`,
    `Amount Paid: ₱${data.amountPaid.toFixed(2)}`,
    `Change: ₱${data.change.toFixed(2)}`,
    data.paymentMethod === 'GCASH' && data.gcashRef ? `GCash Ref #: ${data.gcashRef}` : '',
    '',
    '      Thank you for shopping!',
    '         Please come again!',
    '\n\n\n'
  ];

  const receiptContent = receiptLines.join('\n');

  try {
    printer.printDirect({
      data: receiptContent,
      printer: 'POS58 Printer(3)', // Use the exact name as shown in Windows
      type: 'RAW',
      success: function (jobID) {
        console.log('Print job sent with ID:', jobID);
        res.status(200).json({ success: true, message: 'Receipt printed successfully', jobId: jobID });
      },
      error: function (err) {
        console.error('Print failed:', err);
        res.status(500).json({ success: false, message: 'Print failed: ' + err.message });
      }
    });
  } catch (error) {
    console.error('Server error:', error);
    res.status(500).json({ success: false, message: 'Server error: ' + error.message });
  }
});

app.listen(PORT, () => {
  console.log(`Thermal print server running on http://localhost:${PORT}`);
}); 