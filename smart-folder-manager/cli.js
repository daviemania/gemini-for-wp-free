#!/usr/bin/env node
// cli.js
const inquirer = require('inquirer');
const chalk = require('chalk');
const path = require('path');
const fs = require('fs-extra');
const SmartFolderManager = require('./organize');

// ASCII Art Banner
const banner = `
╔═══════════════════════════════════════════════╗
║                                               ║
║    🤖  Smart Folder Manager v2.0             ║
║    Intelligent File Organization              ║
║                                               ║
╚═══════════════════════════════════════════════╝
`;

async function validatePath(input) {
  if (!input || input.trim() === '') {
    return 'Please enter a valid path';
  }
  
  const resolvedPath = path.resolve(input);
  const exists = await fs.pathExists(resolvedPath);
  
  if (!exists) {
    return `Path does not exist: ${resolvedPath}`;
  }
  
  try {
    const stat = await fs.stat(resolvedPath);
    if (!stat.isDirectory()) {
      return 'Path must be a directory';
    }
  } catch (error) {
    return `Cannot access path: ${error.message}`;
  }
  
  return true;
}

async function confirmAction(message, defaultValue = false) {
  const { confirmed } = await inquirer.prompt([
    {
      type: 'confirm',
      name: 'confirmed',
      message,
      default: defaultValue
    }
  ]);
  return confirmed;
}

async function main() {
  console.log(chalk.blue.bold(banner));
  console.log(chalk.gray('Organize, analyze, and optimize your files with ease\n'));

  try {
    // Main menu
    const { action } = await inquirer.prompt([
      {
        type: 'list',
        name: 'action',
        message: 'What would you like to do?',
        choices: [
          { name: '📁 Organize files by type', value: 'byType' },
          { name: '🤖 Smart organization (AI-powered)', value: 'smart' },
          { name: '🔍 Find and remove duplicates', value: 'duplicates' },
          { name: '🧹 Clean empty directories', value: 'clean' },
          { name: '📊 Analyze folder structure', value: 'analyze' },
          { name: '⏪ Undo last operation', value: 'undo' },
          new inquirer.Separator(),
          { name: '🚪 Exit', value: 'exit' }
        ],
        pageSize: 10
      }
    ]);

    if (action === 'exit') {
      console.log(chalk.blue('👋 Goodbye! Thanks for using Smart Folder Manager'));
      return;
    }

    // Get folder path with validation
    const { folderPath } = await inquirer.prompt([
      {
        type: 'input',
        name: 'folderPath',
        message: 'Enter folder path:',
        default: process.cwd(),
        validate: validatePath,
        transformer: (input) => {
          return input ? path.resolve(input) : process.cwd();
        }
      }
    ]);

    const resolvedPath = path.resolve(folderPath);
    const manager = new SmartFolderManager(resolvedPath);

    console.log(chalk.gray(`\nWorking directory: ${resolvedPath}\n`));

    switch (action) {
      case 'byType':
        await handleOrganizeByType(manager);
        break;
      
      case 'smart':
        await handleSmartOrganize(manager);
        break;
      
      case 'duplicates':
        await handleDuplicates(manager);
        break;
      
      case 'clean':
        await handleClean(manager);
        break;
      
      case 'analyze':
        await handleAnalyze(manager);
        break;
      
      case 'undo':
        await handleUndo(manager);
        break;
    }

    // Ask if user wants to do another operation
    const { continueWork } = await inquirer.prompt([
      {
        type: 'confirm',
        name: 'continueWork',
        message: '\nWould you like to perform another operation?',
        default: false
      }
    ]);

    if (continueWork) {
      console.log('\n' + '='.repeat(50) + '\n');
      await main();
    } else {
      console.log(chalk.blue('\n✨ All done! Have a great day!'));
    }

  } catch (error) {
    if (error.isTtyError) {
      console.error(chalk.red('❌ Prompt couldn\'t be rendered in this environment'));
    } else if (error.message === 'User force closed the prompt') {
      console.log(chalk.yellow('\n👋 Operation cancelled by user'));
    } else {
      console.error(chalk.red(`\n❌ Error: ${error.message}`));
    }
    process.exit(1);
  }
}

async function handleOrganizeByType(manager) {
  const options = await inquirer.prompt([
    {
      type: 'confirm',
      name: 'dryRun',
      message: 'Run in dry-run mode? (preview changes without moving files)',
      default: true
    },
    {
      type: 'confirm',
      name: 'createDateFolders',
      message: 'Create date-based subfolders? (organize by year-month)',
      default: false
    }
  ]);

  if (!options.dryRun) {
    const proceed = await confirmAction(
      '⚠️  This will move files in the directory. Continue?',
      false
    );
    if (!proceed) {
      console.log(chalk.yellow('Operation cancelled'));
      return;
    }
  }

  await manager.organizeByType(options);
  
  if (options.dryRun) {
    const runForReal = await confirmAction(
      '\n✅ Preview complete. Run the operation for real?',
      false
    );
    
    if (runForReal) {
      manager.resetStats();
      await manager.organizeByType({ ...options, dryRun: false });
    }
  }
}

async function handleSmartOrganize(manager) {
  const options = await inquirer.prompt([
    {
      type: 'confirm',
      name: 'dryRun',
      message: 'Run in dry-run mode?',
      default: true
    },
    {
      type: 'confirm',
      name: 'customPatterns',
      message: 'Use custom patterns?',
      default: false
    }
  ]);

  let patterns = undefined;
  
  if (options.customPatterns) {
    console.log(chalk.yellow('\nℹ️  Using default patterns. Edit organize.js to customize patterns.'));
  }

  if (!options.dryRun) {
    const proceed = await confirmAction(
      '⚠️  This will move files based on smart patterns. Continue?',
      false
    );
    if (!proceed) {
      console.log(chalk.yellow('Operation cancelled'));
      return;
    }
  }

  await manager.organizeSmart({ dryRun: options.dryRun, patterns });

  if (options.dryRun) {
    const runForReal = await confirmAction(
      '\n✅ Preview complete. Run the operation for real?',
      false
    );
    
    if (runForReal) {
      manager.resetStats();
      await manager.organizeSmart({ dryRun: false, patterns });
    }
  }
}

async function handleDuplicates(manager) {
  const options = await inquirer.prompt([
    {
      type: 'list',
      name: 'minSize',
      message: 'Minimum file size to check:',
      choices: [
        { name: 'All files', value: 0 },
        { name: '1 KB and larger', value: 1024 },
        { name: '100 KB and larger', value: 102400 },
        { name: '1 MB and larger', value: 1048576 },
        { name: '10 MB and larger', value: 10485760 }
      ],
      default: 1024
    },
    {
      type: 'list',
      name: 'hashMethod',
      message: 'Hash method:',
      choices: [
        { name: 'Full content (slower, more accurate)', value: 'full' },
        { name: 'Quick hash (faster, slightly less accurate)', value: 'quick' }
      ],
      default: 'full'
    }
  ]);

  console.log(chalk.yellow('\n🔍 Scanning for duplicates...\n'));

  const duplicates = await manager.findDuplicates({
    autoRemove: false,
    minSize: options.minSize,
    compareByContent: options.hashMethod === 'full',
    showProgress: true
  });

  if (duplicates.length > 0) {
    const { action } = await inquirer.prompt([
      {
        type: 'list',
        name: 'action',
        message: `\nFound ${duplicates.length} duplicate(s). What would you like to do?`,
        choices: [
          { name: '🗑️  Remove all duplicates (keep originals)', value: 'remove' },
          { name: '🕒 Remove all duplicates (keep newest)', value: 'removeKeepNew' },
          { name: '👀 Review duplicates individually', value: 'review' },
          { name: '❌ Do nothing', value: 'nothing' }
        ]
      }
    ]);

    if (action === 'remove') {
      const confirmed = await confirmAction(
        `⚠️  This will permanently delete ${duplicates.length} file(s). Are you sure?`,
        false
      );
      
      if (confirmed) {
        manager.resetStats();
        await manager.findDuplicates({
          autoRemove: true,
          minSize: options.minSize,
          compareByContent: options.hashMethod === 'full',
          showProgress: false,
          keepNewest: false
        });
      }
    } else if (action === 'removeKeepNew') {
      const confirmed = await confirmAction(
        `⚠️  This will delete ${duplicates.length} older duplicate(s). Are you sure?`,
        false
      );
      
      if (confirmed) {
        manager.resetStats();
        await manager.findDuplicates({
          autoRemove: true,
          minSize: options.minSize,
          compareByContent: options.hashMethod === 'full',
          showProgress: false,
          keepNewest: true
        });
      }
    } else if (action === 'review') {
      console.log(chalk.yellow('\nℹ️  Manual review feature coming soon!'));
    }
  }
}

async function handleClean(manager) {
  const confirmed = await confirmAction(
    '🧹 This will remove all empty directories. Continue?',
    true
  );

  if (confirmed) {
    await manager.removeEmptyDirs();
  } else {
    console.log(chalk.yellow('Operation cancelled'));
  }
}

async function handleAnalyze(manager) {
  const { detailed } = await inquirer.prompt([
    {
      type: 'confirm',
      name: 'detailed',
      message: 'Show detailed analysis? (includes largest/oldest files)',
      default: true
    }
  ]);

  await manager.analyzeStructure({ detailed });
}

async function handleUndo(manager) {
  const confirmed = await confirmAction(
    '⏪ Undo the last operation? This will restore moved files.',
    false
  );

  if (confirmed) {
    const success = await manager.undo();
    if (!success) {
      console.log(chalk.yellow('\nℹ️  Note: Undo is only available for operations performed in this session.'));
    }
  } else {
    console.log(chalk.yellow('Undo cancelled'));
  }
}

// Handle command line arguments
if (require.main === module) {
  const args = process.argv.slice(2);

  if (args.length === 0) {
    // Interactive mode
    main().catch(error => {
      console.error(chalk.red(`Fatal error: ${error.message}`));
      process.exit(1);
    });
  } else {
    // Command line mode
    const command = args[0];
    const targetPath = args[1] || process.cwd();
    const flags = args.slice(2);

    const manager = new SmartFolderManager(targetPath);

    (async () => {
      try {
        switch (command) {
          case 'organize':
            await manager.organizeByType({ 
              dryRun: flags.includes('--dry-run'),
              createDateFolders: flags.includes('--by-date')
            });
            break;

          case 'smart':
            await manager.organizeSmart({ 
              dryRun: flags.includes('--dry-run')
            });
            break;

          case 'duplicates':
          case 'dupes':
            await manager.findDuplicates({ 
              autoRemove: flags.includes('--remove'),
              minSize: flags.includes('--min-1mb') ? 1048576 : 1024,
              compareByContent: !flags.includes('--quick')
            });
            break;

          case 'clean':
            await manager.removeEmptyDirs();
            break;

          case 'analyze':
          case 'stats':
            await manager.analyzeStructure({ 
              detailed: flags.includes('--detailed')
            });
            break;

          case 'help':
          case '--help':
          case '-h':
            printHelp();
            break;

          default:
            console.log(chalk.red(`Unknown command: ${command}\n`));
            printHelp();
            process.exit(1);
        }
      } catch (error) {
        console.error(chalk.red(`\n❌ Error: ${error.message}`));
        process.exit(1);
      }
    })();
  }
}

function printHelp() {
  console.log(chalk.blue.bold('\n📖 Smart Folder Manager - Help\n'));
  console.log(chalk.white('Usage: smart-organize [command] [path] [options]\n'));
  
  console.log(chalk.yellow('Commands:'));
  console.log('  organize          Organize files by type/extension');
  console.log('  smart             Smart organization based on patterns');
  console.log('  duplicates        Find and manage duplicate files');
  console.log('  clean             Remove empty directories');
  console.log('  analyze           Analyze folder structure\n');
  
  console.log(chalk.yellow('Options:'));
  console.log('  --dry-run         Preview changes without making them');
  console.log('  --remove          Auto-remove duplicates (duplicates command)');
  console.log('  --quick           Use quick hash for duplicates (faster)');
  console.log('  --min-1mb         Only check files >= 1MB (duplicates)');
  console.log('  --by-date         Create date-based folders (organize)');
  console.log('  --detailed        Show detailed analysis (analyze)\n');
  
  console.log(chalk.yellow('Examples:'));
  console.log('  smart-organize                                    # Interactive mode');
  console.log('  smart-organize organize ~/Downloads               # Organize Downloads');
  console.log('  smart-organize duplicates . --remove --min-1mb    # Remove large duplicates');
  console.log('  smart-organize analyze ~/Documents --detailed     # Detailed analysis');
  console.log('  smart-organize smart ~/Desktop --dry-run          # Preview smart organize\n');
}

module.exports = main;
