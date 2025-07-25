import escpos from 'escpos';
// Import USB adapter
escpos.USB = require('escpos-usb');

export default async function handler(req, res) {
  try {
    // List available USB devices
    const devices = escpos.USB.findPrinter();
    console.log('Available USB devices:', devices);

    if (devices.length === 0) {
      return res.status(400).json({
        success: false,
        message: 'No USB printers found'
      });
    }

    // Use the first available device
    const device = new escpos.USB();
    
    // Create printer instance
    const options = { encoding: "GB18030" /* default */ }
    const printer = new escpos.Printer(device, options);

    return new Promise((resolve) => {
      device.open(function(error){
        if(error) {
          console.error('Printer connection error:', error);
          res.status(500).json({ 
            success: false, 
            message: 'Printer connection error: ' + error.message,
            availableDevices: devices
          });
          return resolve();
        }

        printer
          .font('a')
          .align('ct')
          .style('b')
          .size(1, 1)
          .text('Test Print')
          .text('------------------------')
          .style('normal')
          .text('If you can read this,')
          .text('printer is working!')
          .text('------------------------')
          .cut()
          .close();

        console.log('Test print successful');
        res.status(200).json({ 
          success: true, 
          message: 'Test print successful',
          availableDevices: devices
        });
        resolve();
      });
    });

  } catch (error) {
    console.error("Printer test error:", error);
    return res.status(500).json({
      success: false,
      message: 'Printer test error: ' + error.message,
      error: error.stack
    });
  }
} 