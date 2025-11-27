#!/usr/bin/env node
const license = process.env.FREEMIUM_LICENSE;

const PREMIUM_CMDS = ['ollamachat', 'openrouterchat', 'githubchat', 'claude:code', 'organize:smart', 'organize:interactive', 'organize:dupes', 'exa'];

if ( PREMIUM_CMDS.includes( process.argv[2] ) && ! license ) {
    console.error( 'Premium license required (set FREEMIUM_LICENSE env).\nFree: npm run chatwmcp' );
    process.exit(1);
}
console.log( 'License OK or free tier.' );
