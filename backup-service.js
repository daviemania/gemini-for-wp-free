const simpleGit = require('simple-git');
const fs = require('fs-extra');
const path = require('path');
const cron = require('node-cron');

class GitAwareBackup {
  constructor(projectPath, backupDir, retentionDays = 7) {
    this.projectPath = projectPath;
    this.backupDir = backupDir;
    this.retentionDays = retentionDays;
    this.git = simpleGit(projectPath);
    this.lastBackupHash = this.loadLastBackupHash();
  }

  loadLastBackupHash() {
    try {
      const hashFile = path.join(this.backupDir, 'last_backup_commit');
      if (fs.existsSync(hashFile)) {
        return fs.readFileSync(hashFile, 'utf8').trim();
      }
    } catch (error) {
      console.log('No previous backup hash found, will create first backup.');
    }
    return null;
  }

  saveLastBackupHash(hash) {
    try {
      const hashFile = path.join(this.backupDir, 'last_backup_commit');
      fs.writeFileSync(hashFile, hash);
    } catch (error) {
      console.error('Failed to save backup hash:', error);
    }
  }

  async getCurrentCommitHash() {
    try {
      const log = await this.git.log(['-1']);
      return log.latest?.hash || 'no-commits';
    } catch (error) {
      return 'no-repo';
    }
  }

  async hasNewCommits() {
    const currentHash = await this.getCurrentCommitHash();
    
    if (!this.lastBackupHash) {
      return true; // First backup
    }
    
    return currentHash !== this.lastBackupHash;
  }

  async getGitStatus() {
    try {
      const status = await this.git.status();
      return {
        isClean: status.isClean(),
        modified: status.modified,
        created: status.not_added,
        deleted: status.deleted
      };
    } catch (error) {
      return { isClean: true, modified: [], created: [], deleted: [] };
    }
  }

  async createIntelligentBackup() {
    console.log('🔍 Checking for changes...');
    
    const timestamp = new Date().toISOString().replace(/[:.]/g, '-');
    const commitHash = await this.getCurrentCommitHash();
    const gitStatus = await this.getGitStatus();
    
    const hasChanges = !gitStatus.isClean;
    const hasNewCommits = await this.hasNewCommits();
    
    // Only skip if no changes AND no new commits since last backup
    if (!hasChanges && !hasNewCommits) {
      console.log('✅ No changes detected since last backup. Skipping...');
      return null;
    }

    const backupFileName = `gemini-project-${timestamp}-${commitHash.substring(0, 8)}.tar.gz`;
    const backupFile = path.join(this.backupDir, backupFileName);

    await fs.ensureDir(this.backupDir);

    // Create metadata
    const metadata = {
      timestamp: new Date().toISOString(),
      commitHash: commitHash,
      hasUncommittedChanges: hasChanges,
      modifiedFiles: gitStatus.modified,
      newFiles: gitStatus.created,
      deletedFiles: gitStatus.deleted,
      branch: await this.getCurrentBranch()
    };

    // Save metadata
    const metadataFile = path.join(this.backupDir, `metadata-${timestamp}.json`);
    await fs.writeJson(metadataFile, metadata, { spaces: 2 });

    // Create the actual backup
    await this.createTarBackup(backupFile);
    
    // Update last backup hash
    this.lastBackupHash = commitHash;
    this.saveLastBackupHash(commitHash);
    
    console.log(`✅ Intelligent backup created: ${backupFileName}`);
    console.log(`📁 Backup size: ${(fs.statSync(backupFile).size / 1024 / 1024).toFixed(2)} MB`);
    console.log(`📝 Changes detected: ${hasChanges ? 'YES' : 'NO'}`);
    console.log(`🔄 New commits: ${hasNewCommits ? 'YES' : 'NO'}`);
    
    this.cleanOldBackups();
    return backupFile;
  }

  async getCurrentBranch() {
    try {
      const branch = await this.git.branch();
      return branch.current;
    } catch (error) {
      return 'unknown';
    }
  }

  async createTarBackup(backupFile) {
    const { execSync } = require('child_process');
    
    console.log('📦 Creating compressed backup...');
    
    // Exclude .git directory and node_modules to save space
    execSync(`tar -czf "${backupFile}" --exclude=".git" --exclude="node_modules" -C "${this.projectPath}" .`, {
      stdio: 'inherit'
    });
  }

  cleanOldBackups() {
    console.log('🧹 Cleaning up old backups...');
    
    const files = fs.readdirSync(this.backupDir);
    const now = Date.now();
    const msPerDay = 24 * 60 * 60 * 1000;
    let deletedCount = 0;

    files.forEach(file => {
      if (file.startsWith('gemini-project-') && file.endsWith('.tar.gz') || 
          file.startsWith('metadata-') && file.endsWith('.json')) {
        const filePath = path.join(this.backupDir, file);
        const stats = fs.statSync(filePath);
        const ageDays = (now - stats.mtime.getTime()) / msPerDay;
        
        if (ageDays > this.retentionDays) {
          fs.unlinkSync(filePath);
          console.log(`🗑️  Deleted old backup: ${file}`);
          deletedCount++;
        }
      }
    });
    
    console.log(`✅ Cleanup completed: ${deletedCount} old files removed`);
  }
}

// Create backup instance
const backup = new GitAwareBackup(
  '/home/bitnami/gemini-project/',
  '/home/bitnami/backups/',
  7  // Keep 7 days of backups
);

// Function to run backup manually
async function runBackup() {
  try {
    console.log('🚀 Starting intelligent backup...');
    await backup.createIntelligentBackup();
    console.log('✅ Backup process completed');
  } catch (error) {
    console.error('❌ Backup failed:', error);
  }
}

// For PM2 service - run backup immediately and then schedule daily
console.log('⏰ Starting backup service...');
console.log('💡 Manual backup: node backup-service.js');

// Run backup immediately on startup
runBackup().then(() => {
  console.log('✅ Initial backup completed');
});

// Schedule daily backup at 2 AM
console.log('⏰ Scheduling daily backups at 2 AM...');
cron.schedule('0 2 * * *', async () => {
  console.log('🕑 Scheduled backup triggered');
  await runBackup();
});

console.log('🔮 Backup service is now running and will execute daily at 2 AM');
