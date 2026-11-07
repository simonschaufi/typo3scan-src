#!/usr/bin/env bash
#
# Regenerate src/Resources/Private/links.txt from the *.rst files that exist
# in src/Resources/Private/Changelog/.
#
# links.txt maps every vendored changelog file to its path on docs.typo3.org
# and is read by the getOnlineDocument Twig filter, so it must stay in sync
# with the changelog data (see _AGENTS.md, "Adding support for a new TYPO3
# major version", step 2).
#
# Usage: ./update-links.sh

set -euo pipefail

# Byte-wise sorting for both the generated file and comm, independent of the
# local collation (this is also the order the existing links.txt uses).
export LC_ALL=C

readonly ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
readonly PRIVATE_DIR="${ROOT_DIR}/src/Resources/Private"
readonly CHANGELOG_DIR="${PRIVATE_DIR}/Changelog"
readonly LINKS_FILE="${PRIVATE_DIR}/links.txt"

if [[ ! -d "${CHANGELOG_DIR}" ]]; then
    echo "error: changelog directory not found: ${CHANGELOG_DIR}" >&2
    exit 1
fi

new_file="$(mktemp)"
old_file="$(mktemp)"
trap 'rm -f "${new_file}" "${old_file}"' EXIT

# One path per line, relative to src/Resources/Private/, e.g.
# Changelog/13.4/Important-105175-....rst
(
    cd "${PRIVATE_DIR}"
    find Changelog -type f -name '*.rst' | sort
) > "${new_file}"

if [[ ! -s "${new_file}" ]]; then
    echo "error: no rst files found below ${CHANGELOG_DIR}" >&2
    exit 1
fi

if [[ -f "${LINKS_FILE}" ]]; then
    sort "${LINKS_FILE}" > "${old_file}"
else
    : > "${old_file}"
fi

new_count="$(wc -l < "${new_file}" | tr -d ' ')"

if cmp -s "${new_file}" "${old_file}"; then
    echo "links.txt is already up to date (${new_count} entries)"
    exit 0
fi

added="$(comm -13 "${old_file}" "${new_file}" | wc -l | tr -d ' ')"
removed="$(comm -23 "${old_file}" "${new_file}" | wc -l | tr -d ' ')"

cp "${new_file}" "${LINKS_FILE}"

echo "updated ${LINKS_FILE}: ${new_count} entries (+${added} / -${removed})"

git add src/Resources/Private/links.txt
