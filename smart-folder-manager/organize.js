// organize.js
const fs = require('fs-extra');
const path = require('path');
const chalk = require('chalk');
const crypto = require('crypto');
const { promisify } = require('util');
const stream = require('stream');
const pipeline = promisify(stream.pipeline);

// Helper function to recursively get files
async function getFilesRecursive(dir, options = {}) {
  const { ignore = [], includeHidden = false } = options;
  const files = [];
  
  async function scan(currentDir) {
    try {
      const items = await fs.readdir(currentDir);
      
      for (const item of items) {
        // Skip hidden files unless includeHidden is true
        if (!includeHidden && item.startsWith('.')) continue;
        
        const fullPath = path.join(currentDir, item);
        
        // Check if path should be ignored
        const relativePath = path.relative(dir, fullPath);
        const shouldIgnore = ignore.some(pattern => {
          if (pattern.endsWith('/**')) {
            const dirName = pattern.replace('/**', '');
            return relativePath.startsWith(dirName) || relativePath.includes(`/${dirName}/`) || item === dirName;
          }
          return item === pattern || relativePath === pattern;
        });
        
        if (shouldIgnore) continue;
        
        try {
          const stat = await fs.stat(fullPath);
          
          if (stat.isDirectory()) {
            await scan(fullPath);
          } else if (stat.isFile()) {
            files.push(fullPath);
          }
        } catch (err) {
          // Skip files we can't access
          console.error(chalk.red(`Cannot access: ${fullPath}`));
        }
      }
    } catch (err) {
      console.error(chalk.red(`Cannot read directory: ${currentDir}`));
    }
  }
  
  await scan(dir);
  return files;
}

async function getDirectoriesRecursive(dir, options = {}) {
  const { ignore = [] } = options;
  const dirs = [];
  
  async function scan(currentDir) {
    try {
      const items = await fs.readdir(currentDir);
      
      for (const item of items) {
        if (item.startsWith('.')) continue;
        
        const fullPath = path.join(currentDir, item);
        const relativePath = path.relative(dir, fullPath);
        
        const shouldIgnore = ignore.some(pattern => {
          if (pattern.endsWith('/**')) {
            const dirName = pattern.replace('/**', '');
            return relativePath.startsWith(dirName) || relativePath.includes(`/${dirName}/`) || item === dirName;
          }
          return item === pattern;
        });
        
        if (shouldIgnore) continue;
        
        try {
          const stat = await fs.stat(fullPath);
          
          if (stat.isDirectory()) {
            dirs.push(fullPath);
            await scan(fullPath);
          }
        } catch (err) {
          // Skip directories we can't access
        }
      }
    } catch (err) {
      // Skip if we can't read the directory
    }
  }
  
  await scan(dir);
  return dirs;
}

class SmartFolderManager {
  constructor(basePath = process.cwd()) {
    this.basePath = path.resolve(basePath);
    this.stats = {
      moved: 0,
      duplicatesFound: 0,
      errors: 0,
      organized: 0,
      spaceRecovered: 0
    };
    this.operationLog = [];
  }

  /**
   * Organize files by type/extension with improved error handling
   */
  async organizeByType(options = {}) {
    const {
      dryRun = false,
      excludeDirs = ['node_modules', '.git', '.DS_Store', 'Organized'],
      targetBase = this.basePath,
      createDateFolders = false
    } = options;

    console.log(chalk.blue(`📁 Organizing files in: ${this.basePath}`));
    if (dryRun) {
      console.log(chalk.yellow('🔍 DRY RUN - No files will be moved\n'));
    }

    try {
      // Validate base path exists
      if (!await fs.pathExists(this.basePath)) {
        throw new Error(`Path does not exist: ${this.basePath}`);
      }

      const items = await fs.readdir(this.basePath);
      const filesProcessed = [];

      for (const item of items) {
        const fullPath = path.join(this.basePath, item);

        // Skip excluded directories and hidden files
        if (excludeDirs.includes(item) || item.startsWith('.')) {
          continue;
        }

        try {
          const stat = await fs.stat(fullPath);

          if (stat.isFile()) {
            const ext = path.extname(item).toLowerCase() || 'no-extension';
            const category = this.getFileCategory(ext);
            
            let targetDir = path.join(targetBase, category);
            
            // Optional: create date-based subfolders
            if (createDateFolders) {
              const date = new Date(stat.mtime);
              const yearMonth = `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}`;
              targetDir = path.join(targetDir, yearMonth);
            }

            const targetPath = path.join(targetDir, item);

            // Skip if source and target are the same
            if (fullPath === targetPath) continue;

            if (!dryRun) {
              await fs.ensureDir(targetDir);
              const finalPath = await this.resolveConflict(targetPath);
              await fs.move(fullPath, finalPath);

              this.stats.moved++;
              this.logOperation('move', fullPath, finalPath);
              console.log(chalk.green(`✓ Moved: ${item} → ${path.relative(this.basePath, finalPath)}`));
            } else {
              console.log(chalk.cyan(`📋 Would move: ${item} → ${path.relative(this.basePath, targetDir)}/`));
            }
            
            filesProcessed.push(item);
          }
        } catch (error) {
          this.stats.errors++;
          console.error(chalk.red(`❌ Error processing ${item}: ${error.message}`));
        }
      }

      if (filesProcessed.length === 0) {
        console.log(chalk.yellow('⚠️  No files found to organize'));
      }

      this.printStats();
      return filesProcessed;
    } catch (error) {
      this.stats.errors++;
      console.error(chalk.red(`❌ Error during organization: ${error.message}`));
      throw error;
    }
  }

  /**
   * Smart organization with enhanced pattern matching
   */
  async organizeSmart(options = {}) {
    const {
      patterns = {
        'Projects/AI': /gemini|claude|chatgpt|ai-assistant|openai/i,
        'Projects/Web': /react|vue|angular|nextjs|website/i,
        'Code/JavaScript': /\.(js|jsx|ts|tsx)$/i,
        'Code/Python': /\.(py|ipynb)$/i,
        'Code/Other': /\.(java|cpp|c|go|rust|rb)$/i,
        'Documents/Text': /\.(md|txt)$/i,
        'Documents/Office': /\.(pdf|docx?|pptx?|xlsx?)$/i,
        'Media/Images': /\.(jpg|jpeg|png|gif|svg|webp|ico)$/i,
        'Media/Videos': /\.(mp4|avi|mov|mkv|webm)$/i,
        'Media/Audio': /\.(mp3|wav|flac|ogg|m4a)$/i,
        'Archives': /\.(zip|tar|gz|rar|7z|bz2)$/i,
        'Config': /\.(json|yaml|yml|toml|ini|env)$/i
      },
      dryRun = false,
      minFileSize = 0,
      maxFileSize = Infinity
    } = options;

    console.log(chalk.blue(`🤖 Smart organizing: ${this.basePath}\n`));

    const allFiles = await this.getAllFiles(this.basePath);
    const organizedFiles = new Set();

    for (const file of allFiles) {
      try {
        const fileName = path.basename(file);
        const stat = await fs.stat(file);

        // Skip files outside size range
        if (stat.size < minFileSize || stat.size > maxFileSize) {
          continue;
        }

        for (const [category, pattern] of Object.entries(patterns)) {
          if (pattern.test(fileName) || pattern.test(file)) {
            const targetDir = path.join(this.basePath, 'Organized', category);
            const targetPath = path.join(targetDir, fileName);

            // Skip if already in target location
            if (file.startsWith(targetDir)) break;

            if (!dryRun) {
              await fs.ensureDir(targetDir);
              const finalPath = await this.resolveConflict(targetPath);
              await fs.move(file, finalPath);

              this.stats.moved++;
              this.logOperation('smart-move', file, finalPath);
              console.log(chalk.green(`✓ Smart moved: ${fileName} → Organized/${category}/`));
            } else {
              console.log(chalk.cyan(`📋 Would smart move: ${fileName} → Organized/${category}/`));
            }
            
            organizedFiles.add(file);
            break;
          }
        }
      } catch (error) {
        this.stats.errors++;
        console.error(chalk.red(`❌ Error processing ${path.basename(file)}: ${error.message}`));
      }
    }

    if (organizedFiles.size === 0) {
      console.log(chalk.yellow('\n⚠️  No files matched smart organization patterns'));
    }

    this.printStats();
    return Array.from(organizedFiles);
  }

  /**
   * Enhanced duplicate detection with progress and better performance
   */
  async findDuplicates(options = {}) {
    const {
      autoRemove = false,
      minSize = 1024, // 1KB minimum by default
      showProgress = true,
      compareByContent = true,
      keepNewest = false
    } = options;

    console.log(chalk.blue(`🔍 Searching for duplicates in: ${this.basePath}\n`));

    const files = await this.getAllFiles(this.basePath);
    const duplicates = [];
    const sizeMap = new Map();

    // First pass: group by size (quick check)
    console.log(chalk.gray(`Analyzing ${files.length} files...\n`));
    
    for (const file of files) {
      try {
        const stat = await fs.stat(file);
        if (stat.size < minSize) continue;

        const size = stat.size;
        if (!sizeMap.has(size)) {
          sizeMap.set(size, []);
        }
        sizeMap.get(size).push({ path: file, mtime: stat.mtime });
      } catch (error) {
        console.error(chalk.red(`Error reading ${file}: ${error.message}`));
      }
    }

    // Second pass: hash potential duplicates
    let checkedGroups = 0;
    const totalGroups = Array.from(sizeMap.values()).filter(g => g.length > 1).length;

    for (const [size, fileGroup] of sizeMap) {
      if (fileGroup.length > 1) {
        checkedGroups++;
        
        if (showProgress) {
          console.log(chalk.gray(`[${checkedGroups}/${totalGroups}] Checking ${fileGroup.length} files of size ${this.formatFileSize(size)}`));
        }

        const hashes = new Map();

        for (const fileInfo of fileGroup) {
          try {
            const hash = compareByContent 
              ? await this.getFileHash(fileInfo.path)
              : await this.getQuickHash(fileInfo.path);
            
            if (hashes.has(hash)) {
              const original = hashes.get(hash);
              
              // Determine which to keep if auto-removing
              const shouldKeep = keepNewest 
                ? (original.mtime > fileInfo.mtime ? original : fileInfo)
                : original;
              const shouldRemove = keepNewest
                ? (original.mtime > fileInfo.mtime ? fileInfo : original)
                : fileInfo;

              duplicates.push({
                original: shouldKeep.path,
                duplicate: shouldRemove.path,
                size: size,
                hash: hash
              });
              
              this.stats.duplicatesFound++;
              this.stats.spaceRecovered += size;
            } else {
              hashes.set(hash, fileInfo);
            }
          } catch (error) {
            console.error(chalk.red(`Error hashing file ${fileInfo.path}: ${error.message}`));
          }
        }
      }
    }

    // Handle duplicates
    console.log(''); // Empty line for spacing
    
    if (duplicates.length > 0) {
      console.log(chalk.yellow(`📦 Found ${duplicates.length} duplicate files (${this.formatFileSize(this.stats.spaceRecovered)} total):\n`));

      for (const [index, dup] of duplicates.entries()) {
        console.log(chalk.yellow(`${index + 1}. Original: ${path.relative(this.basePath, dup.original)}`));
        console.log(chalk.red(`   Duplicate: ${path.relative(this.basePath, dup.duplicate)}`));
        console.log(chalk.gray(`   Size: ${this.formatFileSize(dup.size)}\n`));

        if (autoRemove) {
          await this.removeDuplicate(dup.duplicate);
        }
      }

      if (!autoRemove) {
        console.log(chalk.blue('💡 Tip: Run with autoRemove option to automatically remove duplicates'));
      } else {
        console.log(chalk.green(`\n✨ Recovered ${this.formatFileSize(this.stats.spaceRecovered)} of disk space!`));
      }
    } else {
      console.log(chalk.green('🎉 No duplicates found!'));
    }

    return duplicates;
  }

  /**
   * Remove empty directories recursively
   */
  async removeEmptyDirs(options = {}) {
    const { excludeDirs = ['node_modules', '.git'] } = options;
    
    console.log(chalk.blue(`🧹 Cleaning empty directories in: ${this.basePath}\n`));

    const allDirs = await this.getAllDirectories(this.basePath);
    let removedCount = 0;

    // Sort by depth (deepest first) to handle nested empty dirs
    allDirs.sort((a, b) => b.split(path.sep).length - a.split(path.sep).length);

    for (const dir of allDirs) {
      const dirName = path.basename(dir);
      
      // Skip excluded directories
      if (excludeDirs.some(excluded => dir.includes(excluded))) {
        continue;
      }

      try {
        const items = await fs.readdir(dir);
        if (items.length === 0) {
          await fs.remove(dir);
          removedCount++;
          console.log(chalk.green(`✓ Removed: ${path.relative(this.basePath, dir)}/`));
        }
      } catch (error) {
        // Directory might have been already removed or inaccessible
      }
    }

    console.log(chalk.blue(`\n📊 Removed ${removedCount} empty director${removedCount === 1 ? 'y' : 'ies'}`));
    return removedCount;
  }

  /**
   * Enhanced folder structure analysis
   */
  async analyzeStructure(options = {}) {
    const { detailed = false } = options;
    
    console.log(chalk.blue('📊 Analyzing folder structure...\n'));

    const files = await this.getAllFiles(this.basePath);
    const analysis = {
      totalFiles: files.length,
      totalSize: 0,
      byExtension: new Map(),
      byCategory: new Map(),
      largest: [],
      oldest: [],
      newest: []
    };

    const fileDetails = [];

    for (const file of files) {
      try {
        const stat = await fs.stat(file);
        analysis.totalSize += stat.size;

        const ext = path.extname(file).toLowerCase() || 'no-extension';
        const category = this.getFileCategory(ext);

        // Count by extension
        const extCount = analysis.byExtension.get(ext) || { count: 0, size: 0 };
        extCount.count++;
        extCount.size += stat.size;
        analysis.byExtension.set(ext, extCount);

        // Count by category
        const catCount = analysis.byCategory.get(category) || { count: 0, size: 0 };
        catCount.count++;
        catCount.size += stat.size;
        analysis.byCategory.set(category, catCount);

        if (detailed) {
          fileDetails.push({
            path: file,
            size: stat.size,
            mtime: stat.mtime,
            ctime: stat.ctime
          });
        }
      } catch (error) {
        console.error(chalk.red(`Error analyzing ${file}: ${error.message}`));
      }
    }

    // Find largest, oldest, newest files
    if (detailed && fileDetails.length > 0) {
      fileDetails.sort((a, b) => b.size - a.size);
      analysis.largest = fileDetails.slice(0, 5);

      fileDetails.sort((a, b) => a.mtime - b.mtime);
      analysis.oldest = fileDetails.slice(0, 5);

      fileDetails.sort((a, b) => b.mtime - a.mtime);
      analysis.newest = fileDetails.slice(0, 5);
    }

    // Display results
    console.log(chalk.blue.bold('📁 Folder Analysis Results:\n'));
    console.log(chalk.white(`   Total Files: ${analysis.totalFiles.toLocaleString()}`));
    console.log(chalk.white(`   Total Size: ${this.formatFileSize(analysis.totalSize)}`));
    console.log(chalk.white(`   Average File Size: ${this.formatFileSize(analysis.totalSize / analysis.totalFiles)}\n`));

    console.log(chalk.blue('📂 Files by Category:'));
    const sortedCategories = Array.from(analysis.byCategory.entries())
      .sort((a, b) => b[1].count - a[1].count);
    
    for (const [category, data] of sortedCategories) {
      console.log(chalk.cyan(`   ${category.padEnd(15)}: ${String(data.count).padStart(4)} files (${this.formatFileSize(data.size)})`));
    }

    if (detailed) {
      console.log(chalk.blue('\n📈 Top 5 Extensions by Count:'));
      const sortedExts = Array.from(analysis.byExtension.entries())
        .sort((a, b) => b[1].count - a[1].count)
        .slice(0, 5);
      
      for (const [ext, data] of sortedExts) {
        console.log(chalk.cyan(`   ${ext.padEnd(15)}: ${String(data.count).padStart(4)} files (${this.formatFileSize(data.size)})`));
      }

      console.log(chalk.blue('\n🔝 Largest Files:'));
      for (const file of analysis.largest) {
        console.log(chalk.yellow(`   ${this.formatFileSize(file.size).padEnd(10)} - ${path.relative(this.basePath, file.path)}`));
      }
    }

    return analysis;
  }

  /**
   * Undo last operation (requires operation log)
   */
  async undo() {
    if (this.operationLog.length === 0) {
      console.log(chalk.yellow('⚠️  No operations to undo'));
      return false;
    }

    console.log(chalk.blue('⏪ Undoing last operation...\n'));
    
    const lastOps = this.operationLog.pop();
    let undoneCount = 0;

    for (const op of lastOps.reverse()) {
      try {
        if (op.type === 'move' || op.type === 'smart-move') {
          await fs.move(op.to, op.from);
          console.log(chalk.green(`✓ Restored: ${path.basename(op.from)}`));
          undoneCount++;
        }
      } catch (error) {
        console.error(chalk.red(`❌ Error undoing ${path.basename(op.from)}: ${error.message}`));
      }
    }

    console.log(chalk.blue(`\n✨ Undone ${undoneCount} operation(s)`));
    return true;
  }

  // Helper methods
  getFileCategory(ext) {
    const categories = {
      images: ['.jpg', '.jpeg', '.png', '.gif', '.svg', '.webp', '.bmp', '.ico', '.tiff'],
      documents: ['.pdf', '.doc', '.docx', '.txt', '.md', '.rtf', '.odt', '.tex'],
      spreadsheets: ['.xlsx', '.xls', '.csv', '.ods'],
      presentations: ['.pptx', '.ppt', '.key', '.odp'],
      code: ['.js', '.ts', '.py', '.java', '.cpp', '.c', '.html', '.css', '.json', '.jsx', '.tsx', '.go', '.rs', '.rb', '.php'],
      archives: ['.zip', '.tar', '.gz', '.rar', '.7z', '.bz2', '.xz'],
      media: ['.mp4', '.mp3', '.avi', '.mov', '.wav', '.mkv', '.flac', '.webm'],
      executables: ['.exe', '.app', '.dmg', '.deb', '.rpm'],
      fonts: ['.ttf', '.otf', '.woff', '.woff2'],
      config: ['.yaml', '.yml', '.toml', '.ini', '.env', '.conf']
    };

    for (const [category, exts] of Object.entries(categories)) {
      if (exts.includes(ext)) return category;
    }

    return 'other';
  }

  async getFileHash(filePath) {
    return new Promise((resolve, reject) => {
      const hash = crypto.createHash('sha256');
      const stream = fs.createReadStream(filePath);

      stream.on('data', data => hash.update(data));
      stream.on('end', () => resolve(hash.digest('hex')));
      stream.on('error', reject);
    });
  }

  async getQuickHash(filePath) {
    // Hash only first and last 4KB for faster comparison
    const stat = await fs.stat(filePath);
    const chunkSize = 4096;
    const hash = crypto.createHash('md5');

    const fd = await fs.open(filePath, 'r');
    
    try {
      // First chunk
      const buffer1 = Buffer.alloc(Math.min(chunkSize, stat.size));
      await fs.read(fd, buffer1, 0, buffer1.length, 0);
      hash.update(buffer1);

      // Last chunk (if file is large enough)
      if (stat.size > chunkSize) {
        const buffer2 = Buffer.alloc(chunkSize);
        await fs.read(fd, buffer2, 0, buffer2.length, stat.size - chunkSize);
        hash.update(buffer2);
      }

      return hash.digest('hex');
    } finally {
      await fs.close(fd);
    }
  }

  async resolveConflict(targetPath) {
    if (!await fs.pathExists(targetPath)) {
      return targetPath;
    }

    const parsed = path.parse(targetPath);
    let counter = 1;

    while (true) {
      const newPath = path.join(parsed.dir, `${parsed.name} (${counter})${parsed.ext}`);
      if (!await fs.pathExists(newPath)) {
        return newPath;
      }
      counter++;
      
      if (counter > 1000) {
        throw new Error('Too many file conflicts');
      }
    }
  }

  async getAllFiles(dir, options = {}) {
    const { includeHidden = false } = options;
    
    const ignore = ['node_modules/**', '.git/**', '.DS_Store'];
    
    return await getFilesRecursive(dir, { ignore, includeHidden });
  }

  async getAllDirectories(dir) {
    const ignore = ['node_modules/**', '.git/**', '.DS_Store'];
    
    return await getDirectoriesRecursive(dir, { ignore });
  }

  formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    if (bytes < 0) return 'Invalid';
    
    const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
    const i = Math.min(Math.floor(Math.log(bytes) / Math.log(1024)), sizes.length - 1);
    return (bytes / Math.pow(1024, i)).toFixed(2) + ' ' + sizes[i];
  }

  async removeDuplicate(filePath) {
    try {
      await fs.remove(filePath);
      this.logOperation('delete', filePath, null);
      console.log(chalk.red(`   🗑️  Removed: ${path.relative(this.basePath, filePath)}`));
    } catch (error) {
      console.error(chalk.red(`   ❌ Error removing duplicate: ${error.message}`));
    }
  }

  logOperation(type, from, to) {
    if (this.operationLog.length === 0 || this.operationLog[this.operationLog.length - 1].batch !== true) {
      this.operationLog.push([]);
    }
    
    this.operationLog[this.operationLog.length - 1].push({ type, from, to, timestamp: Date.now() });
  }

  printStats() {
    console.log(chalk.blue('\n📊 Operation Summary:'));
    console.log(chalk.green(`   Files moved: ${this.stats.moved}`));
    console.log(chalk.yellow(`   Duplicates found: ${this.stats.duplicatesFound}`));
    
    if (this.stats.spaceRecovered > 0) {
      console.log(chalk.cyan(`   Space recovered: ${this.formatFileSize(this.stats.spaceRecovered)}`));
    }
    
    if (this.stats.errors > 0) {
      console.log(chalk.red(`   Errors: ${this.stats.errors}`));
    }
  }

  resetStats() {
    this.stats = {
      moved: 0,
      duplicatesFound: 0,
      errors: 0,
      organized: 0,
      spaceRecovered: 0
    };
  }
}

module.exports = SmartFolderManager;
