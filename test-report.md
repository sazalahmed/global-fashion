in  http://devrizvi.xyz/admin/products/create route

issues: 
  1. need to give an option to make order (positioning), for an example if user give position 1 for id : 20 then it will show as first product. and update the rest of the products positions for next like: 2,3,4,. there shouldn't any position conflict, if user want to change a product to 3, then after all will be increased by 1.
  2. remove the supplier
  3. Country of Origin
  4. Weight (grams)
  5. there should be an option for upload thumbnail image
  6. Product Images upload not working
  7. add a field for lang description, Long Description should have text editor (do this for all other description fields)
  9. change all &amp; to &
  10. print lable is not working
  11. Discount Type, there is no option to input the discount amount
  12. there should be add variable product option in the product creation time
  13. need to change the category structure. like tree view in the sidebar. when user select the sub category then parent category will be selected automatically, and if user select the child category only in that case it should be select the parent and sub category automatically, you can see the image for example: "C:\Users\ORIGIN TECH\OneDrive\Pictures\lightshot\Screenshot_1.png"
  14. Sync to eCommerce remove it will be true by default 
  15. remove Show in POS it will be true by default
  16. remove this Track Stock, it will be true by default
  17. Purchase Unit and Sales Unit will be selected pcs by default in the form. so user can change it to any other type
  18. if there is no branch then hide the branch field and also side from the top bar. and give option to create a new branch in the top bar
  19. rename MAIN WAREHOUSE to Stock
  20. add option to the sidebar "Add Product"


21. in this page,  we are missing the size guide and size chart. like: "C:\Users\ORIGIN TECH\OneDrive\Pictures\lightshot\Screenshot_2.png"



in http://devrizvi.xyz/admin/pos route

issues: 
  1. cart list : item names are broken 
  ex: <div class="bp-pos-cart-item-name">' + escapeHtml(item.name) + '</div> <div class="d-flex flex-column align-items-end"><div class="bp-pos-cart-item-line-total">' + formatBDT(lineTotal) + '</div><button type="button" class="bp-pos-cart-item-remove mt-1" data-idx="0" title="Remove"><i class="fa-solid fa-xmark"></i></button></div>
  showing like this. need to fix this
  2. same issues in the Select Variant modal
  3. need to redesign the items lists, grid view is looks bad, make it more attractive and production ready, there should be a image 


in http://devrizvi.xyz/admin/quotations/create route


issues: 
' + productLabel + '
SKU: ' + escapeHtml(sku) + ' 
this issues in the many pages, product create, edit page, quotation create, quotation edit page, purchase page, also same for variants. fix this issue
2. show product image before product name and when click on the image then that image should show in venbox modal
3. also give option to upload attachments

in http://devrizvi.xyz/admin/purchases/create route
  issues:
    1. there is no selling price option. 
    2. after grand total will update the product costs? 
    3. give an option to create a product in the purchase page. it will redirect to the product create page in a new tab. after create the product then it should show in the purchase product list. that means it will be a live search features
    4. give an option to add a discount in the purchase page. and also give an option to add a discount in the product creation page.
    5. remove Payment Terms
    6. handle Branch conditionaly. if there is branch in that case show the branch option in the form. if there is no branch then hide the branch field and also side from the top bar. and give option to create a new branch in the top bar

in http://devrizvi.xyz/admin/pos/settings page

issues:
1. remove Auto-focus barcode input
2. there will be an option if want to show the full page invoice then it will be true by default
3. there will be an option if want to show the thermal receipt then it will be true by default
4. and make them dynamic in the pos

in http://devrizvi.xyz/admin/products route 

issues:
1. rename clone to duplicate and make it working.
2. product view page has issue Method Illuminate\Support\Collection::hasPages does not exist.
3. barcode is not working from the action buttons
4. stock history is not working
5. when click on the delete button it ask twice before deleting

in http://devrizvi.xyz/admin/sales route

issues:
1. there many options are missing. I need a filter block like these options.
<ul class="category" style="text-align: center"> <li class="sale_status_manual  active " data-status="2"> <div> 0 </div> Pending </li> <li class="sale_status_manual " data-status="5"> <div> 0 </div> Packing </li> <li class="sale_status_manual " data-status="1"> <div> 0 </div> Courier </li> <li class="sale_status_manual " data-status="7"> <div> 275 </div> Delivered </li> <li class="sale_status_manual " data-status="8"> <div> 141 </div> Cancelled </li> <li class="sale_status_manual " data-status="4"> <div> 0 </div> Return Received </li> <li class="sale_status_manual " data-status="11"> <div> 1 </div> Returned </li> <li class="sale_status_manual " data-status="3"> <div> 0 </div> Draft </li> <li class="sale_status_manual " data-status="9"> <div> 0 </div> On-Hold </li> <li class="sale_status_manual " data-status="10"> <div> 0 </div> Exchange </li> <li class="sale_status_manual " data-status="14"> <div> 0 </div> Incompleted </li> <li class="sale_status_manual " data-status="0"> <div> 417 </div> All </li> </ul>

2. there will be bulk actions. like: bulk assign user to that sales, status update, send to courier, courier status, return, delivered, print, label print.
3. color the sales list rows based on the status. if cancel then show red color, if delivered then show green color. if return then show yellow color.
4. in the sales action buttions, give options: genereate invoice, courier check, view, add complaint, view payments, return receive, add payment, delete

in http://devrizvi.xyz/admin/sales/create route

issues: 
1. branch will be same as we discussed in the http://devrizvi.xyz/admin/purchases/create route
2. there will be few options, Order Discount Type, discount ammount, Shipping Cost, Sale Status, Courier, Sale Note, Staff Note


remove Reconciliation from stock block from sidebar


in http://devrizvi.xyz/admin/settings route
issues:
1. from payment methods table add method is not working, edit button is not working, actually these data are not dynamic. and I am not able to see the transaction history for each for the payment method. I need to add a new table for that.
2. there shouldn't any static payment method option
3. from Courier & Delivery tab, remove add courier option
4. after updating courier config then that service should be active and opened default
5. when I save any data from the settings tab that tab should be active after save or reload
