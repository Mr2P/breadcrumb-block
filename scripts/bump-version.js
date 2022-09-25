#!/usr/bin/env node

"use strict";

/*!
 * Script to bump version.
 * Copyright 2022 Phi Phan (mrphipv@gmail.com)
 */

const fs = require("fs");
const fsPromises = fs.promises;
const path = require("path");
const globby = require("globby");
const semver = require("semver");
const { version } = JSON.parse(fs.readFileSync("./package.json"));

const VERBOSE = process.argv.includes("--verbose");
const DRY_RUN =
  process.argv.includes("--dry") || process.argv.includes("--dry-run");

// These are the filetypes we only care about replacing the version
const GLOB = ["*.php"];
const GLOBBY_OPTIONS = {
  cwd: path.join(__dirname, ".."),
  gitignore: true,
};

// https://stackoverflow.com/a/69409483/1038868
const parseArgv = (key) => {
  // Return true if the key exists and a value is defined
  if (process.argv.includes(`--${key}`)) return true;

  const value = process.argv.find((element) => element.startsWith(`--${key}=`));

  // Return null if the key does not exist and a value is not defined
  if (!value) return null;

  return value.replace(`--${key}=`, "");
};

// Blame TC39... https://github.com/benjamingr/RegExp.escape/issues/37
function regExpQuote(string) {
  return string.replace(/[$()*+-.?[\\\]^{|}]/g, "\\$&");
}

function regExpQuoteReplacement(string) {
  return string.replace(/\$/g, "$$");
}

async function replaceVersion(file, oldVersion, newVersion) {
  const originalString = await fsPromises.readFile(file, "utf8");
  const newString = originalString.replace(
    new RegExp(regExpQuote(oldVersion), "g"),
    regExpQuoteReplacement(newVersion)
  );

  // No need to move any further if the strings are identical
  if (originalString === newString) {
    return;
  }

  if (VERBOSE) {
    console.log(`FILE: ${file}`);
  }

  if (DRY_RUN) {
    return;
  }

  await fsPromises.writeFile(file, newString, "utf8");
}

async function main(args) {
  const type = parseArgv("type");
  if (!["patch", "minor", "major"].includes(type)) {
    console.error("USAGE: bump-version --type=patch|minor|major");
    console.error("Got arguments:", args);
    process.exit(1);
  }

  // Get new version
  const newVersion = semver.inc(version, type);

  try {
    const files = await globby(GLOB, GLOBBY_OPTIONS);

    await Promise.all(
      files.map((file) => replaceVersion(file, version, newVersion))
    );

    // Replace version in package.json
    await replaceVersion(
      path.join(__dirname, "..", "package.json"),
      `"version": "${version}"`,
      `"version": "${newVersion}"`
    );

    console.log(`Bump into ${newVersion}`);
  } catch (error) {
    console.error(error);
    process.exit(1);
  }
}

main(process.argv.slice(1));
