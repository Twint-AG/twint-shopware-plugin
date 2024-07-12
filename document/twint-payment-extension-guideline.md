<p align="center" style="font-size:150%"><b>TWINT Payment Extension Guideline</b></p>

## Installation

### 1. Download the extension

Download the plugin zip file from out git repo

Or download form the Shopware Extensions Store. 

<img src="./screenshots/download-zip.png" alt="Download the extension in zip" width="600" height="auto">

### 2. Upload the extension

- Go to `Extensions -> My extensions`.
- Click the `Upload extension`.

<img src="./screenshots/upload-extension.png" alt="Enable the TWINT Payment extension" width="900" height="auto">

- Click `Confirm` on the warning popup.

<img src="./screenshots/warning-popup.png" alt="Confirm the warning popup" width="600" height="auto">

### 3 Install the extension

After the extension was imported -> Click the `Install` link to install the extention

<img src="./screenshots/extension-imported.png" alt="Enable the TWINT Payment extension" width="900" height="auto">


## Enable the extension

#### 1. Login to the Admin console panel

#### 2. Go to `Extensions -> My extensions`

Under the `Apps` tab -> Ensure that `Twint Payment` is enabled

<img src="./screenshots/twint-enable-extension.png" alt="Enable the TWINT Payment extension" width="900" height="auto">

## Configure the extension

### Enter the Credential

#### 1. Login to the Admin console panel

#### 2. Go to `Settings -> Extensions -> TWINT Credentials`

- Enter the `Merchant ID`.
- Under the `Certificate File` click `Choose file` and browse to the `*.p12` certificate file.
- Enter the `Certificate Password`.
- **For test environment:** please turn on the `Switch to test mode` switch or else leave it off.

> 🚩 **Note:**
> 
> After entering the certification password, please wait for the flash message saying `Certificate validation successful` before clicking Save. 

<img src="./screenshots/cert-validated.png" alt="Certification validated" width="300" height="auto">

- Click the `Save` button at the top right corner.

<img src="./screenshots/twint-credential.png" alt="Configure TWINT credential" width="900" height="auto">

#### 3. Go to `Settings -> Extensions -> TWINT Express Checkout`

Under the `Display options` section -> Select where we want the `Twint Express Checkout` button to be displayed.

#### 4. Go to `Settings -> Payment methods`

- Ensure that the below payment methods are enabled:
    - TWINT - Express Checkout
    - TWINT - Regular Checkout
- We can further custom the payment method (e.g. add logo) by click the `Edit details` link next to each payment method.

<img src="./screenshots/twint-payment-methods.png" alt="Active TWINT payment methods" width="900" height="auto">

## Configure Shopware and the Sale channel

### Currency

> 🚩 **Note:**
>
> TWINT payment extension supports **CHF** currency only. Please make sure CHF currency is added to the Sale channel (also know as the Storefront).
>
> If we already have **CHF** currency created please ignore this section.

#### 1. Login to the Admin console panel

#### 2. Go to `Settings -> Currencies`

- Click `Add currency` button
- Enter the currency information as desired. For example:
    - **Name:** Swiss francs
    - **ISO code:** CHF
    - **Short name:** *anything*
    - **Symbol:** Fr
    - **Conversion factor:**
        - If CHF is the only currency enabled for the shop: Enter `1`
        - If the shop supports multiple currencies:
            - CHF is the first currency: Enter `1`
            - CHF is not the first currency: Enter the `Conversion factor` against the first currency *i.e.* `1.1` for `CHF = 1st currency * 1.1`
- Configure the `Price rounding` section. To display decimal places in the Sale channel, input the number of decimal places to be displayed to the `Decimals` field.
- Click the `Save` button.

<img src="./screenshots/multiple-currencies.png" alt="Multiple currencies" width="900" height="auto">

<img src="./screenshots/chf-currency.png" alt="CHF Currency" width="900" height="auto">

### Sale channel

> 🚩 **Note:**
>
> At this stage, the Sale channel should be set up and configured. The information below serves as kind reminder.  
> Below are just some information need our attention on.

- Please ensure the below payment methods are added for the Sale channel:
    - TWINT - Express Checkout
    - TWINT - Regular Checkout
- Ensure CHF currency is added to your sale channel.

<img src="./screenshots/sale-channel.png" alt="Add payment methods and currencies to the Sale channel" width="900" height="auto">