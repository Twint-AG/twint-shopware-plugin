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

For running testing in CI/CD process. Should run it in headless mode
```bash
npm run test:headless
```
