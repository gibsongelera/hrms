# GitHub Pages Setup Instructions

## Fix 404 Error - Step by Step

### Step 1: Enable GitHub Pages
1. Go to your repository: https://github.com/gibsongelera/hrms
2. Click on **Settings** (top menu)
3. Scroll down to **Pages** in the left sidebar
4. Under **Source**, select **GitHub Actions** (NOT "Deploy from a branch")
5. Click **Save**

### Step 2: Check Workflow Status
1. Go to the **Actions** tab in your repository
2. Look for "Deploy to GitHub Pages" workflow
3. Make sure it has run and completed successfully
4. If it failed, check the error logs

### Step 3: Wait for Deployment
- After enabling GitHub Actions as the source, the workflow should run automatically
- It may take 1-2 minutes for the site to be available
- The site URL will be: `https://gibsongelera.github.io/hrms/`

### Step 4: Verify Files
Make sure these files exist in the `index` branch:
- ✅ `index.html` (in root)
- ✅ `login.html` (in root)
- ✅ `dashboard.html` (in root)
- ✅ `.github/workflows/pages.yml` (workflow file)

### Troubleshooting

**If you still see 404:**
1. Check the Actions tab - is the workflow running?
2. Wait 2-3 minutes after enabling GitHub Actions
3. Clear your browser cache
4. Try accessing: `https://gibsongelera.github.io/hrms/index.html` directly
5. Check repository visibility (must be public for free GitHub Pages)

**If workflow fails:**
- Check the error message in the Actions tab
- Make sure the `index` branch exists and has the files
- Verify GitHub Pages is enabled in Settings

## Current Status
- ✅ Workflow file created (`.github/workflows/pages.yml`)
- ✅ HTML files created (index.html, login.html, dashboard.html)
- ✅ Files pushed to `index` branch
- ⚠️ **Action Required**: Enable GitHub Pages in repository settings

