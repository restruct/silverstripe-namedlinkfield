# Browser-test targets for this module, sourced by the shared runner
# (~/Sites/0_ss-mods-maintenance/tools/browser/run.sh) and by .github/workflows/browser-tests.yml.
# Plain bash assignments only.
# Ports are assigned in ~/Sites/0_ss-mods-maintenance/tools/browser/PORTS.md; take new ones there.

BROWSER_PACKAGE="restruct/silverstripe-namedlinkfield"
BROWSER_TARGETS="ss5"

# This branch is the v2 line (2.1.x), which serves Silverstripe 5 (and 4, not browser-tested).
# Silverstripe 6 is the main branch (3.x); its own targets.sh and CI job cover it.
# Empty ref = the checkout the runner was given; CI tests only targets with an empty ref.
SS5_RECIPE="^5"
SS5_PHP="8.3"
SS5_PORT="8893"
SS5_SRC_REF=""
