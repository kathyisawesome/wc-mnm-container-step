> Warning This project is archived. Please check out [Variable Mix and Match](https://github.com/kathyisawesome/wc-mnm-variable) as an alternative.

# WooCommerce Mix and Match: Container Step

## Quickstart

This is a developmental repo. Clone this repo and run `npm install && npm run build`   
OR    
[Download latest release](https://github.com/kathyisawesome/wc-mnm-container-step/releases/latest) 

## What's This?

Mini-extension for [WooCommerce Mix and Match](https://woocommerce.com/products/woocommerce-mix-and-match-products) that forces Mix and Match container size to be in quantity multiples, ie: 6,12,18,etc. 

![image](https://user-images.githubusercontent.com/507025/80157388-5155aa00-8583-11ea-9050-d3ddead27af5.png)

## Important

1. This is proof of concept and not officially supported in any way.
2. Requires Mix and Match Products 2.0.
3. This is intended for containers that are dyanmically priced and packed separetely as there's currently no system for resolving total into X shipping packages at least at the Mix and Match level (some shipping plugins may be able to do this via box packer algorithms).... ex: 24 items aren't automatically parsed into 2, 12 pack shipping boxes. If you need fixed prices and are packing together you may want to check out [Grouped Mix and Match](https://github.com/kathyisawesome/wc-mnm-grouped)

## Usage

In the "Mix and Match" tab of the Product Data metabox you will now see a "Container step size" input. This is the number that the total quantity must be a multiple of in order for the container to be valid..

![metabox showing Container step size text input](https://user-images.githubusercontent.com/507025/80157273-08055a80-8583-11ea-95b7-29dcc757accd.png)

### Automatic plugin updates

Plugin updates can be enabled by installing the [Git Updater](https://git-updater.com/) plugin.
