import { NextResponse } from 'next/server';

export async function GET() {
  try {
    // Test PHP backend connectivity
    const phpUrl = 'http://localhost/Enguio_Project/backend.php';
    console.log('Testing PHP backend connectivity...');
    
    const testResponse = await fetch(phpUrl, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({ 
        action: 'test_connection',
        test: 'data'
      }),
    });
    
    if (!testResponse.ok) {
      const errorText = await testResponse.text();
      console.error('PHP backend error:', errorText);
      return NextResponse.json({ 
        success: false,
        message: 'PHP backend error',
        status: testResponse.status,
        error: errorText
      }, { status: 500 });
    }
    
    const result = await testResponse.json();
    return NextResponse.json({ 
      success: true,
      message: 'Backend API is working',
      phpResponse: result,
      timestamp: new Date().toISOString()
    });
  } catch (error) {
    console.error('Test API error:', error);
    return NextResponse.json({ 
      success: false,
      message: 'Backend API error',
      error: error.message,
      timestamp: new Date().toISOString()
    }, { status: 500 });
  }
}

export async function POST(request) {
  try {
    const body = await request.json();
    return NextResponse.json({ 
      success: true, 
      message: 'POST request received',
      data: body,
      timestamp: new Date().toISOString()
    });
  } catch (error) {
    return NextResponse.json({ 
      success: false, 
      error: error.message 
    }, { status: 400 });
  }
} 