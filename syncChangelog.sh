#!/usr/bin/env bash
#
# Synchronize the vendored changelog data.
#
#   source: .temp/Changelog                     (symlink -> local TYPO3 core checkout,
#                                                typo3/sysext/core/Documentation/Changelog)
#   target: resources/changelog
#
# The target is mirrored from the source:
#   - files that only exist in the source are added
#   - files whose content differs are overwritten
#   - files that no longer exist in the source are removed
# Every change is reported, grouped by added / updated / removed.
#
# Afterwards ./updateLinks.sh is run (unless --no-links is given), because
# resources/links.txt is generated from the target directory and
# must stay in sync with it.
#
# Usage: ./syncChangelog.sh [-n|--dry-run] [--no-links] [-h|--help]

set -euo pipefail

# Byte-wise sorting, independent of the local collation (same as updateLinks.sh).
export LC_ALL=C

readonly ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
readonly SOURCE_DIR="${ROOT_DIR}/.temp/Changelog"
readonly TARGET_DIR="${ROOT_DIR}/resources/changelog"
readonly LINKS_SCRIPT="${ROOT_DIR}/updateLinks.sh"

dry_run=false
update_links=true

usage() {
    cat <<'EOF'
Usage: ./syncChangelog.sh [-n|--dry-run] [--no-links] [-h|--help]

Synchronize resources/changelog from .temp/Changelog
and report added/updated/removed files.

Options:
  -n, --dry-run   only report what would change, do not touch any file
  --no-links      do not run ./updateLinks.sh afterwards
  -h, --help      show this help
EOF
}

while [[ $# -gt 0 ]]; do
    case "$1" in
        -n | --dry-run) dry_run=true ;;
        --no-links) update_links=false ;;
        -h | --help)
            usage
            exit 0
            ;;
        *)
            echo "error: unknown argument: $1" >&2
            usage >&2
            exit 1
            ;;
    esac
    shift
done

if [[ ! -e "${SOURCE_DIR}" ]]; then
    echo "error: source changelog not found: ${SOURCE_DIR}" >&2
    exit 1
fi

if [[ ! -d "${TARGET_DIR}" ]]; then
    echo "error: target changelog not found: ${TARGET_DIR}" >&2
    exit 1
fi

src_list="$(mktemp)"
tgt_list="$(mktemp)"
added_list="$(mktemp)"
updated_list="$(mktemp)"
removed_list="$(mktemp)"
trap 'rm -f "${src_list}" "${tgt_list}" "${added_list}" "${updated_list}" "${removed_list}"' EXIT

# One path per line, relative to the changelog dir, e.g. 13.4/Important-105175-....rst
# (cd + find follows the source symlink, so .temp/Changelog needs no -L).
(cd "${SOURCE_DIR}" && find . -type f | sed 's|^\./||' | sort) > "${src_list}"
(cd "${TARGET_DIR}" && find . -type f | sed 's|^\./||' | sort) > "${tgt_list}"

if [[ ! -s "${src_list}" ]]; then
    echo "error: no files found below ${SOURCE_DIR}" >&2
    exit 1
fi

while IFS= read -r rel; do
    if [[ ! -e "${TARGET_DIR}/${rel}" ]]; then
        printf '%s\n' "${rel}" >> "${added_list}"
    elif ! cmp -s "${SOURCE_DIR}/${rel}" "${TARGET_DIR}/${rel}"; then
        printf '%s\n' "${rel}" >> "${updated_list}"
    fi
done < "${src_list}"

comm -23 "${tgt_list}" "${src_list}" > "${removed_list}"

added_count="$(wc -l < "${added_list}" | tr -d ' ')"
updated_count="$(wc -l < "${updated_list}" | tr -d ' ')"
removed_count="$(wc -l < "${removed_list}" | tr -d ' ')"
source_count="$(wc -l < "${src_list}" | tr -d ' ')"

print_section() {
    local title="$1" marker="$2" file="$3" count="$4"
    echo "${title} (${count}):"
    if [[ "${count}" -eq 0 ]]; then
        echo "  (none)"
    else
        sed "s|^|  ${marker} |" "${file}"
    fi
    echo
}

if $dry_run; then
    echo "Dry run: ${SOURCE_DIR} -> ${TARGET_DIR}"
else
    echo "Syncing ${SOURCE_DIR} -> ${TARGET_DIR}"
fi
echo

print_section "Added" "+" "${added_list}" "${added_count}"
print_section "Updated" "~" "${updated_list}" "${updated_count}"
print_section "Removed" "-" "${removed_list}" "${removed_count}"

if ! $dry_run; then
    while IFS= read -r rel; do
        mkdir -p "$(dirname "${TARGET_DIR}/${rel}")"
        cp "${SOURCE_DIR}/${rel}" "${TARGET_DIR}/${rel}"
    done < "${added_list}"

    while IFS= read -r rel; do
        cp "${SOURCE_DIR}/${rel}" "${TARGET_DIR}/${rel}"
    done < "${updated_list}"

    while IFS= read -r rel; do
        rm -f "${TARGET_DIR}/${rel}"
    done < "${removed_list}"

    # Drop directories that only existed because of removed files.
    find "${TARGET_DIR}" -mindepth 1 -type d -empty -delete
fi

if ((added_count + updated_count + removed_count == 0)); then
    echo "Changelog is already in sync (${source_count} files)."
else
    echo "${added_count} added, ${updated_count} updated, ${removed_count} removed (${source_count} files in source)."
fi

if ((added_count + removed_count > 0)) && ! $dry_run; then
    # The file set changed, so links.txt has to be regenerated as well.
    if $update_links && [[ -x "${LINKS_SCRIPT}" ]]; then
        echo
        "${LINKS_SCRIPT}"
    elif ! $update_links; then
        echo "note: links.txt not regenerated (--no-links), run ./updateLinks.sh manually."
    else
        echo "note: links.txt not regenerated (${LINKS_SCRIPT} missing), run ./updateLinks.sh manually."
    fi
elif $dry_run && ((added_count + removed_count > 0)); then
    echo "note: ./updateLinks.sh would have to be run after applying this."
fi

git add resources/changelog
