import { printer } from 'printer';

export default async function handler(req, res) {
  try {
    // Get list of installed printers
    const printers = printer.getPrinters();
    console.log('Available printers:', printers);

    // Find our target printer
    const targetPrinter = printers.find(p => p.name === "POS58 Printer(3)");
    
    if (!targetPrinter) {
      return res.status(400).json({
        success: false,
        message: 'Printer "POS58 Printer(3)" not found',
        availablePrinters: printers.map(p => p.name)
      });
    }

    // Try to print a test page
    return new Promise((resolve) => {
      printer.printDirect({
        data: "\x1B\x40" + "Test Print\n\nIf you can read this,\nprinter is working!\n\n\n",
        printer: targetPrinter.name,
        type: 'RAW',
        success: function(jobID) {
          console.log("Test print successful. Job ID:", jobID);
          res.status(200).json({
            success: true,
            message: 'Test print successful',
            printer: targetPrinter,
            jobId: jobID
          });
          resolve();
        },
        error: function(err) {
          console.error("Test print failed:", err);
          res.status(500).json({
            success: false,
            message: 'Test print failed: ' + err.message,
            printer: targetPrinter
          });
          resolve();
        }
      });
    });

  } catch (error) {
    console.error("Printer test error:", error);
    return res.status(500).json({
      success: false,
      message: 'Printer test error: ' + error.message
    });
  }
} 