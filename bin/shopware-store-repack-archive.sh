#!/usr/bin/env bash

set -euo pipefail

[ -z "${1+x}" ] && echo "Usage: $0 <archive>" && exit 1

archive="$1"
tmpdir=$(mktemp -d)
out="${1/.zip/-repacked.zip}"

unzip -q "$archive" -d "$tmpdir"

root=$(find "$tmpdir" -mindepth 1 -maxdepth 1 -type d | head -n 1)
rootname=$(basename "$root")
target_root="TwintPayment"
prefix="twint-shopware-"


if [[ ! "$rootname" == "$prefix"* ]]; then
  echo "Error: root folder '$rootname' does not start with prefix '$prefix'" >&2
  rm -rf "$tmpdir"
  exit 1
fi

mv "$root" "$tmpdir/$target_root"

(cd "$tmpdir" && zip -qr "archive.zip" "$target_root")
mv "$tmpdir/archive.zip" "$out"
rm -rf "$tmpdir"

echo "Repacked as $out"
