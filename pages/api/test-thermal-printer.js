import ThermalPrinter from 'node-thermal-printer';

export default async function handler(req, res) {
  try {
    let printer = new ThermalPrinter.printer({
      type: ThermalPrinter.types.EPSON,
      interface: `printer:POS58 Printer(3)`,
      driver: require('printer'),
      options: {
        timeout: 5000
      }
    });

    const isConnected = await printer.isPrinterConnected();
    console.log('Printer connected:', isConnected);

    if (!isConnected) {
      return res.status(400).json({
        success: false,
        message: 'Printer not connected'
      });
    }

    // Print test page
    printer.alignCenter();
    printer.println('Test Print');
    printer.newLine();
    printer.println('If you can read this,');
    printer.println('printer is working!');
    printer.newLine();
    printer.cut();

    try {
      await printer.execute();
      console.log("Test print successful");
      return res.status(200).json({ 
        success: true, 
        message: 'Test print successful',
        printerConnected: isConnected
      });
    } catch (executeError) {
      console.error("Test print failed:", executeError);
      return res.status(500).json({ 
        success: false, 
        message: 'Test print failed: ' + executeError.message,
        printerConnected: isConnected
      });
    }

  } catch (error) {
    console.error("Printer test error:", error);
    return res.status(500).json({
      success: false,
      message: 'Printer test error: ' + error.message,
      error: error.stack
    });
  }
} 