```
Please note that this extension is a BETA release.
This release is not suitable for production/live Magento® environments.
```

# Ginger Payments for Magento® 2 (BETA)


## Installation

1. Go to Magento® 2 root folder

2. Enter following commands to install module:

   ```
   composer require gingerpayments/ginger-magento2
   ```

   Wait while dependencies are updated.
   
3. Enter following commands to enable module:

   ```
   php bin/magento setup:upgrade
   php bin/magento cache:clean
   ```

4. If Magento® is running in production mode, deploy static content: 

   ```
   php bin/magento setup:static-content:deploy
   ```

5. Enable and configure the Ginger Payments extension in Magento® Admin under *Stores* >
   *Configuration* > *Sales* > *Payment Methods* > *Ginger Payments*.
