#!/usr/bin/env node

const { GoogleGenerativeAI } = require('@google/generative-ai');

const readline = require('readline');

const fs = require('fs').promises;

const path = require('path');

const { program } = require('commander');



require('dotenv').config();



program

  .option('-t, --temperature <number>', 'Set the temperature for generation', parseFloat, 0.7)

  .option('-m, --max-tokens <number>', 'Set the max output tokens', parseInt, 2048)

  .option('--model <string>', 'Set the model to use', 'gemini-1.5-flash')

  .parse(process.argv);



const options = program.opts();



const genAI = new GoogleGenerativeAI(process.env.GEMINI_API_KEY);

const rl = readline.createInterface({

  input: process.stdin,

  output: process.stdout

});



const MODEL_OPTIONS = [

  'gemini-2.0-flash',

  'gemini-2.0-flash-lite',

  'gemini-1.5-flash',

  'gemini-1.5-pro',

  'gemini-1.0-pro'

];



let workingModel = options.model;

let chat;

let conversationHistory = [];



async function loadSystemPrompt() {

    try {

        const geminiMdPath = path.join(__dirname, 'GEMINI.md');

        const systemPrompt = await fs.readFile(geminiMdPath, 'utf-8');

        // Prepend the system prompt to the conversation history as the initial context

        conversationHistory.push({ role: "user", parts: [{ text: systemPrompt }] });

        conversationHistory.push({ role: "model", parts: [{ text: "I have reviewed my context and am ready to assist you." }] });

        console.log('✅ System prompt loaded from GEMINI.md');

        return true;

    } catch (error) {

        console.log('⚠️ Could not load GEMINI.md. Continuing without a system prompt.');

        return false;

    }

}



async function testModel(modelName) {

  try {

    process.stdout.write(`   Testing ${modelName}...`);

    const model = genAI.getGenerativeModel({ 

      model: modelName,

      generationConfig: { maxOutputTokens: 1024 }

    });

    const result = await model.generateContent("Say hello in one word");

    await result.response;

    process.stdout.write(` ✅ Success!\n`);

    return true;

  } catch (error) {

    process.stdout.write(` ❌ Failed\n`);

    return false;

  }

}



async function findWorkingModel() {

    if (await testModel(workingModel)) {

        console.log(`\n🎯 Selected model: ${workingModel}`);

        return workingModel;

    }



  console.log(`\n🔍 Model ${workingModel} failed. Testing other available Gemini models...\n`);

  for (const modelName of MODEL_OPTIONS) {

    if (modelName === workingModel) continue; // Skip the one that already failed

    if (await testModel(modelName)) {

      console.log(`\n🎯 Selected model: ${modelName}`);

      return modelName;

    }

  }

  throw new Error('\n❌ No working Gemini model found.');

}



async function startChat() {

    console.log(`\n🔧 Configuration:`);

    console.log(`   - Model: ${workingModel}`);

    console.log(`   - Temperature: ${options.temperature}`);

    console.log(`   - Max Tokens: ${options.maxTokens}\n`);



    const model = genAI.getGenerativeModel({ 

        model: workingModel,

        generationConfig: {

            maxOutputTokens: options.maxTokens,

            temperature: options.temperature,

        }

    });

    chat = model.startChat({

        history: conversationHistory,

        generationConfig: {

            maxOutputTokens: options.maxTokens,

            temperature: options.temperature,

        }

    });

}



async function interactiveChat() {
  try {
    workingModel = await findWorkingModel();
    await loadSystemPrompt();
    await startChat();
    
    console.log(`\n🤖 Gemini CLI (Enhanced) - Ready! (Using ${workingModel})`);
    console.log('💡 Type your questions below (type "exit" to quit)\n');
    
    while (true) {
        const userInput = await new Promise((resolve) => {
            rl.question('👤 You: ', resolve);
        });

        const trimmedInput = userInput.trim();
        
        if (trimmedInput.toLowerCase() === 'exit') {
            console.log('🤖 Gemini: Goodbye! 👋');
            rl.close();
            process.exit(0);
        }

        if (!trimmedInput) continue;

        process.stdout.write('🤖 Gemini: Thinking...');
      
        try {
            const result = await chat.sendMessage(trimmedInput);
            const response = await result.response;
            const text = response.text();
            console.log('\r🤖 Gemini: ' + text + '\n');
            // Update history
            conversationHistory.push({ role: "user", parts: [{ text: trimmedInput }]});
            conversationHistory.push({ role: "model", parts: [{ text: text }]});

        } catch (error) {
            console.log('\r🤖 Gemini: Error - ' + error.message + '\n');
        }
    }
  } catch (error) {
    console.log(error.message);
    rl.close();
    process.exit(1);
  }
}

// For now, we'll focus on the interactive mode to test the new features.
interactiveChat();
