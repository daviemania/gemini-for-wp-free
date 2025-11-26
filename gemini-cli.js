#!/usr/bin/env node

const { GoogleGenerativeAI } = require('@google/generative-ai');
const readline = require('readline');

require('dotenv').config();

const genAI = new GoogleGenerativeAI(process.env.GEMINI_API_KEY);
const rl = readline.createInterface({
  input: process.stdin,
  output: process.stdout
});

// Gemini 2.0 model names
const MODEL_OPTIONS = [
  'gemini-2.0-flash',        // Primary Flash model
  'gemini-2.0-flash-lite',   // Lite version
  'gemini-1.5-flash',        // Fallback to 1.5
  'gemini-1.5-pro',          // Fallback to 1.5 pro
  'gemini-1.0-pro'           // Legacy fallback
];

let workingModel = 'gemini-2.0-flash'; // Default to Gemini 2.0 Flash

async function testModel(modelName) {
  try {
    console.log(`   Testing ${modelName}...`);
    const model = genAI.getGenerativeModel({ 
      model: modelName,
      generationConfig: {
        maxOutputTokens: 1024,
      }
    });
    const result = await model.generateContent("Say hello in one word");
    const response = await result.response;
    console.log(`   ✅ ${modelName}: Success!`);
    return true;
  } catch (error) {
    console.log(`   ❌ ${modelName}: ${error.message.split('\n')[0]}`);
    return false;
  }
}

async function findWorkingModel() {
  console.log('🔍 Testing available Gemini models...\n');
  
  for (const modelName of MODEL_OPTIONS) {
    const isWorking = await testModel(modelName);
    if (isWorking) {
      console.log(`\n🎯 Selected model: ${modelName}`);
      return modelName;
    }
  }
  
  throw new Error('\n❌ No working Gemini model found. Possible issues:\n   • Invalid API key\n   • API key not enabled for Gemini\n   • Regional restrictions\n   • Check: https://aistudio.google.com/app/apikey');
}

async function generateResponse(prompt) {
  try {
    const model = genAI.getGenerativeModel({ 
      model: workingModel,
      generationConfig: {
        maxOutputTokens: 2048,
        temperature: 0.7,
      }
    });
    const result = await model.generateContent(prompt);
    const response = await result.response;
    return response.text();
  } catch (error) {
    throw new Error(`API Error: ${error.message}`);
  }
}

async function interactiveChat() {
  try {
    workingModel = await findWorkingModel();
    console.log(`\n🤖 Gemini CLI - Ready! (Using ${workingModel})`);
    console.log('💡 Type your questions below (type "exit" to quit)\n');
    
    while (true) {
      const question = await new Promise((resolve) => {
        rl.question('👤 You: ', resolve);
      });

      const trimmedQuestion = question.trim();
      
      if (trimmedQuestion.toLowerCase() === 'exit') {
        console.log('🤖 Gemini: Goodbye! 👋');
        rl.close();
        process.exit(0);
      }

      if (!trimmedQuestion) continue;

      process.stdout.write('🤖 Gemini: Thinking...');
      
      try {
        const response = await generateResponse(trimmedQuestion);
        console.log('\r🤖 Gemini: ' + response + '\n');
      } catch (error) {
        console.log('\r🤖 Gemini: Error - ' + error.message + '\n');
        
        // Try to recover by finding a new working model
        console.log('🔄 Attempting to find another working model...');
        try {
          workingModel = await findWorkingModel();
        } catch (e) {
          console.log('❌ Cannot recover. Please check your API key.');
          rl.close();
          process.exit(1);
        }
      }
    }
  } catch (error) {
    console.log(error.message);
    rl.close();
    process.exit(1);
  }
}

// Single question mode
if (process.argv.length > 2) {
  const prompt = process.argv.slice(2).join(' ');
  (async () => {
    try {
      workingModel = await findWorkingModel();
      console.log('👤 You:', prompt);
      const response = await generateResponse(prompt);
      console.log('🤖 Gemini:', response);
    } catch (error) {
      console.log('❌ Error:', error.message);
    }
    process.exit(0);
  })();
} else {
  interactiveChat();
}
