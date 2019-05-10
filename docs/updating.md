## Updating

When you install Regolith, you replace the `origin` remote with your own repository, so you can't just `git pull` to merge in updates to Regolith. It's still fairly easy, though; here's how:

1. `git clone` Reglith into a separate folder from your existing installtion.
1. Open your preferred visual diff tool (e.g, [DeltaWalker](https://www.deltawalker.com/), [Meld](http://meldmerge.org/), [Xcode's FileMerge](https://developer.apple.com/xcode/features/), etc), and compare the two folders.
1. Go through each file that has differences, and merge the changes from Regolith to your installation.
	1. In most cases, this will be pretty simple, but if you've made any customizations to those lines, then you'll need to decide how to resolve the differences.
1. When you've finished copying the changes, test that everything still works as expected.
1. `git add -p .` to review and stage the changes.
1. `git ci -m 'Merge latest Regolith.'`
1. `git push`
1. `bin/deploy.sh`
