### Change directory if you did not do that (from root folder of repository)
`` cd cypress
``

### Install dependencies via npm
```
npm install
```

### Setup environments

```shell
cp cypress.env.dist.json cypress.env.json;

// replace placeholder with your value
sed -i "s|\"__BASE_URL__\"|\"$BASE_URL\"|g" cypress.env.json
```

### Run Test cases

```
npm run test
```

### CI Pipeline integration

#### Dependencies
Reading official document here: https://docs.cypress.io/guides/getting-started/installing-cypress#Linux-Prerequisites

With dockware container what we hosted Shopware instances: we need:
```bash
sudo apt-get update
sudo apt-get install -y xvfb libnss3 libgbm1 libasound libasound2
```
Should run it in headless mode
```bash
npm run test:headless

// or 

npx cypress run --headless --browser chrome
```

Note that for CI container maybe you need to install chrome as well as 
```bash
sudo apt-get update
sudo apt-get install -y wget gnupg
wget -q -O - https://dl.google.com/linux/linux_signing_key.pub | sudo apt-key add -
sudo sh -c 'echo "deb [arch=amd64] https://dl.google.com/linux/chrome/deb/ stable main" >> /etc/apt/sources.list.d/google-chrome.list'
sudo apt-get update
sudo apt-get install -y google-chrome-stable
```
