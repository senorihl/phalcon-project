#!/bin/bash

# This script is used to convert the output from "phpunit --coverage-text"
# into a markdown formatted table. It parses the coverage data,
# then generates a table with class names, statement counts, misses, and coverages.
# The output is designed to be easily readable and suitable for inclusion in
# markdown-based documents or reports.

# Ignore output until reaching coverage report
while IFS= read -r line
do
  if [[ $line == *"Summary"* ]]; then
    read _  # skip overall class coverage
    read _  # skip overall method coverage
    break
  fi
done

echo "| Class | Stmts | Miss | Cover |"
echo "|-------|-------|------|-------|"

# Calculate overall coverage
read overall_coverage
total_stmts=$(echo $overall_coverage | awk -F'[()]' '{print $2}' | awk -F/ '{print $2}')
total_miss=$(echo $overall_coverage | awk -F'[()]' '{print $2}' | awk -F/ '{print $2 - $1}')
total_coverage=$(echo $overall_coverage | awk -F'[:(]' '{print $2}' | tr -d ' ')

echo "| **TOTAL** | **$total_stmts** | **$total_miss** | **$total_coverage** |"

while IFS= read -r line
do
  # If line starts with a letter (assumed to be a class name)
  if [[ $line =~ ^[A-Za-z] ]]; then
    class_name=$(echo $line | cut -d' ' -f1)
    read coverage_info
    stmts=$(echo $coverage_info | awk -F'[()]' '{print $4}' | awk -F/ '{print $2}')
    miss=$(echo $coverage_info | awk -F'[()]' '{print $4}' | awk -F/ '{print $2 - $1}')
    coverage=$(echo $coverage_info | awk -F'[:(]' '{print $4}' | tr -d ' ')

    echo "| \`$class_name\` | $stmts | $miss | $coverage |"
  fi
done