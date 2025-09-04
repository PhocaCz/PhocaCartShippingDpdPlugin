var phParamsPlgPcsDpd = Joomla.getOptions('phParamsPlgPcsDpd');
var phLangPlgPcsDpd = Joomla.getOptions('phLangPlgPcsDpd');

function phSetDpdCheckboxActive(id){
    document.getElementById(id).checked = true;
}

function phGetDpdSelectedShippingMethod() {
    const infoElements = document.getElementsByName('phshippingopt');
    let selectedShippingMethod = null;
    
    for (let i = 0; i < infoElements.length; i++) {
        if (infoElements[i].checked) {
            selectedShippingMethod = infoElements[i].value;
            break;
        }
    }
    return selectedShippingMethod;
}

function phGetDpdDay(dayNr) {
    let day = phLangPlgPcsDpd['MONDAY'];
    switch(dayNr) {
        case "monday": day = phLangPlgPcsDpd['MONDAY']; break;
        case "tuesday": day = phLangPlgPcsDpd['TUESDAY']; break;
        case "wednesday": day = phLangPlgPcsDpd['WEDNESDAY']; break;
        case "thursday": day = phLangPlgPcsDpd['THURSDAY']; break;
        case "friday": day = phLangPlgPcsDpd['FRIDAY']; break;
        case "saturday": day = phLangPlgPcsDpd['SATURDAY']; break;
        case "sunday": day = phLangPlgPcsDpd['SUNDAY']; break;
    }
    return day;
}

async function showDpdSelectedPickupPoint(point) {

    let selectedShippingMethodSuffix = '';
    let selectedShippingMethod = phGetDpdSelectedShippingMethod();

    let infoElement = document.getElementById('dpd-point-info');
    if (selectedShippingMethod !== null) {
        selectedShippingMethodSuffix = '-' + selectedShippingMethod;
        infoElement = document.getElementById('dpd-point-info' + selectedShippingMethodSuffix);
    }


    if (point) {
        /* Display Branch info immediately */
        let info = '';

        let name = point.contactInfo.name;
        let street = point.location.address.street;
        let zip = point.location.address.zip;
        let city = point.location.address.city;
        let country = point.location.address.country;
        let phone = point.contactInfo.phone;
        let email = point.contactInfo.email;
        let web = point.contactInfo.web;
        let latitude = point.location.coordinates.latitude;
        let longitude = point.location.coordinates.longitude;


        info += '<div class="ph-checkout-dpd-info-name">' + name + "<br>" + street + "<br>" + zip + " " + city + '</div>';

        let openHours = '';
        if (phParamsPlgPcsDpd[selectedShippingMethod]['display_opening_hours'] == 1) {
            if (point.openingHours) {
                const daysOrder = [
                    "monday",
                    "tuesday",
                    "wednesday",
                    "thursday",
                    "friday",
                    "saturday",
                    "sunday"
                ];

                //let openHours = "";

                daysOrder.forEach(function(day) {
                    const intervals = point.openingHours[day];

                    if (Array.isArray(intervals) && intervals.length > 0) {
                        openHours += '<div><div>' + phGetDpdDay(day) + '</div><div>';

                        intervals.forEach(function(interval, idx) {
                            if (idx > 0) {
                                openHours += ', ';
                            }
                            openHours += interval.open + ' - ' + interval.close;
                        });

                        openHours += '</div></div>';
                    }
                });
                
                info += '<div class="ph-checkout-dpd-info-opening-hours">' + openHours + '</div>';
            }
        }
        
        infoElement.innerHTML = info;
    
        /* Add Branch info to form fields - to store them */
        if (phParamsPlgPcsDpd[selectedShippingMethod]['fields'].length !== 0) {
            for (let index = 0; index < phParamsPlgPcsDpd[selectedShippingMethod]['fields'].length; ++index) {
                const element = phParamsPlgPcsDpd[selectedShippingMethod]['fields'][index];
                let elementId = 'dpd-field-' + element + selectedShippingMethodSuffix;

                if (document.getElementById(elementId)){
                    if (element == 'street') {
                        document.getElementById(elementId).value = street;
                    } else if (element == 'city') {
                        document.getElementById(elementId).value = city;
                    } else if (element == 'zip') {
                        document.getElementById(elementId).value = zip;
                    } else if (element == 'country') {
                        document.getElementById(elementId).value = country;
                    } else if (element == 'phone') {
                        document.getElementById(elementId).value = phone;
                    } else if (element == 'email') {
                        document.getElementById(elementId).value = email;
                    } else if (element == 'latitude') {
                        document.getElementById(elementId).value = latitude;
                    } else if (element == 'longitude') {
                        document.getElementById(elementId).value = longitude;
                    } else if (element == 'opening_hours') { 
                        if (phParamsPlgPcsDpd[selectedShippingMethod]['display_opening_hours'] == 1) {
                            document.getElementById(elementId).value = openHours;
                        }
                    } else {
                        document.getElementById(elementId).value = point[element];
                    }
                }
                
            }
        }

    } else {
        infoElement.innerText = phLangPlgPcsDpd['PLG_PCS_SHIPPING_DPD_NONE'];
        /* Add Branch info to form fields - clear all values */
        if (phParamsPlgPcsDpd[selectedShippingMethod]['fields'].length !== 0) {
            for (let index = 0; index < phParamsPlgPcsDpd[selectedShippingMethod]['fields'].length; ++index) {
                const element = phParamsPlgPcsDpd[selectedShippingMethod]['fields'][index];
                let elementId = 'dpd-field-' + element + selectedShippingMethodSuffix;
                document.getElementById(elementId).value = '';
            }
        }
    }
};


function phCheckImageExists(url) {
    return new Promise((resolve) => {
        const img = new Image();
        img.onload = function() {
            resolve(true);
        };
        img.onerror = function() {
            resolve(false);
        };
        img.src = url;
    });
}

function closeDpdModal() {
    let selectedShippingMethod = phGetDpdSelectedShippingMethod();
    if (phParamsPlgPcsDpd[selectedShippingMethod]['theme'] == 'uikit') {
        let modal = UIkit.modal('#phPcsDpdPopup' + selectedShippingMethod);
        modal.hide();
    } else {
        let modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('phPcsDpdPopup' + selectedShippingMethod));
        modal.hide();
    }
}

/* Listen to DPD */
window.addEventListener("message", (event) => {

    if(event.data.dpdWidget) {
      showDpdSelectedPickupPoint(event.data.dpdWidget);
      closeDpdModal();
    }
  }, false);


/* Test if method is selected */
document.addEventListener("DOMContentLoaded", function() {
    let button = document.querySelector('.ph-checkout-shipping-save .ph-btn');
    button.addEventListener('click', function(e) {
        
        let selectedShippingMethodSuffix = '';
        let selectedShippingMethod = phGetDpdSelectedShippingMethod();
        
        if (selectedShippingMethod !== null) {
            selectedShippingMethodSuffix = '-' + selectedShippingMethod;
        }
        
        let elementId = 'dpd-field-id' + selectedShippingMethodSuffix;
        let elementDocId = document.getElementById(elementId);
        if (elementDocId) {
            let elementDocIdValue = elementDocId.value;

            let dpdCheckbox = document.getElementById('dpd-checkbox-id' + selectedShippingMethodSuffix).value;
            let dpdCheckboxChecked = document.getElementById(dpdCheckbox).checked;

            if (phParamsPlgPcsDpd[selectedShippingMethod]['validate_pickup_point'] == 1 && dpdCheckboxChecked && elementDocIdValue == '') {
                e.preventDefault();
                alert(phLangPlgPcsDpd['PLG_PCS_SHIPPING_DPD_ERROR_PLEASE_SELECT_PICK_UP_POINT']);
                return false;
            }
        }
        return;
        
    });
});
