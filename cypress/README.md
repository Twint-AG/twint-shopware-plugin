After clone the project - execute

```
npm init
```

Install Cypress

```
npm install cypress --save-dev
```

Install Typescript

```
npm install typescript --save-dev
```

Create `tsconfig.json` file
```
touch tsconfig.json
```

And enter the below content

``` json
{
  "compilerOptions": {
    "target": "ES5",
    "lib": ["ES5", "DOM"],
    "types": ["cypress", "node"]
  },
  "include": ["**/*.ts"]
}
```

Open Cypress

```
npx cypress open
```