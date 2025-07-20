import { NextResponse } from 'next/server';

export async function POST(request) {
  try {
    const body = await request.json();
    
    console.log('Next.js API route received:', body);
    
    // Test if we can reach the PHP backend
    const phpUrl = 'http://localhost/Enguio_Project/backend.php';
    console.log('Attempting to connect to:', phpUrl);
    
    // First, try a simple GET request to see if the server is reachable
    try {
      const testResponse = await fetch(phpUrl, {
        method: 'GET',
        headers: {
          'Content-Type': 'application/json',
        },
      });
      console.log('PHP server test response status:', testResponse.status);
    } catch (testError) {
      console.error('PHP server test failed:', testError.message);
      return NextResponse.json(
        { 
          success: false, 
          error: 'PHP server not reachable',
          details: testError.message,
          message: 'Cannot connect to PHP backend. Make sure XAMPP is running.'
        },
        { status: 500 }
      );
    }
    
    // Now try the actual POST request
    const phpResponse = await fetch(phpUrl, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(body),
    });

    console.log('PHP response status:', phpResponse.status);
    console.log('PHP response headers:', Object.fromEntries(phpResponse.headers.entries()));

    if (!phpResponse.ok) {
      const errorText = await phpResponse.text();
      console.error('PHP response error text:', errorText);
      throw new Error(`PHP backend error: ${phpResponse.status} - ${errorText}`);
    }

    const result = await phpResponse.json();
    console.log('PHP backend response:', result);
    
    return NextResponse.json(result);
    
  } catch (error) {
    console.error('API route error:', error);
    return NextResponse.json(
      { 
        success: false, 
        error: error.message,
        message: 'Failed to communicate with backend',
        timestamp: new Date().toISOString()
      },
      { status: 500 }
    );
  }
}

export async function GET(request) {
  try {
    // Test PHP backend connectivity
    const phpUrl = 'http://localhost/Enguio_Project/backend.php';
    console.log('Testing PHP backend connectivity...');
    
    const testResponse = await fetch(phpUrl, {
      method: 'GET',
      headers: {
        'Content-Type': 'application/json',
      },
    });
    
    return NextResponse.json({ 
      message: 'Backend API is running',
      phpStatus: testResponse.status,
      phpOk: testResponse.ok,
      timestamp: new Date().toISOString()
    });
  } catch (error) {
    return NextResponse.json({ 
      message: 'Backend API error',
      error: error.message,
      timestamp: new Date().toISOString()
    }, { status: 500 });
  }
} 