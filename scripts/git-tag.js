#!/usr/bin/env node

"use strict";

/*!
 * Script to git commit & git tag new version.
 * Copyright 2022 Phi Phan (mrphipv@gmail.com)
 */

const fs = require("fs");
const childProcess = require("child_process");
const { version } = JSON.parse(fs.readFileSync("./package.json"));

function main() {
  childProcess.exec(`git add . && git commit -m "Bumps to version ${version}"`);

  childProcess.exec(`git tag -a v${version} -m "Bumps to version ${version}"`);

  childProcess.exec(`git push origin v${version}`);
}

main();
