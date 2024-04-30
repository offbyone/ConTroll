var region = null;
var regionTab = null;

var itemTable = null;
var itemSaveBtn = null;
var itemUndoBtn = null;
var itemRedoBtn = null;


var testdiv = null;

$(document).ready(function() {
    testdiv = document.getElementById('test');
    setRegion('overview', null);
});

function setRegion(name, id) {
    region = id;

    if(regionTab!=null) {
        regionTab.classList.remove('active');
        regionTab.setAttribute('aria-selected', 'false');
    }

    regionElem = document.getElementById(name + '-tab');
    regionElem.classList.add('active')
    regionElem.setAttribute('aria-selected', 'true');
    regionTab=regionElem;

    if(region != null) { getData(); }
    else { 
        document.getElementById('artItems_table').innerHTML="<p>This is an Overview tab, please select one of the regions above to see the items in that region</p>";
    }
}

function getData() {
    var script = "scripts/getArtItems.php";
    $.ajax({
        method: "GET",
        url: script,
        data: 'region=' + region,
        success: function (data, textStatus, jqXHR) {
            if('error' in data) {
                showError("ERROR in getArt: " + data['error']);
            }
            draw(data, textStatus, jqXHR);
        },
        error: function (jqXHR, textStatus, errorThrown) {
            showError("ERROR in getArt: " + textStatus, jqXHR);
            return false;
        }
    });
}

function draw(data, textStatus, jqXHR) {
    //set buttons
    itemTable = new Tabulator('#artItems_table', {
        mxHeight: "800px",
        history: true,
        data: data['art'],
        layout: 'fitDataTable',
        pagination: true,
        paginationSize: 50,
        paginationSizeSelector: [10, 25, 50, 100, true], // enable page size select with these options
        columns: [
            {title: 'id', field: 'id', visible: false},
            {title: 'Name', field: 'exhibitorName', headerSort: true, headerFilter: 'list', headerFilterParams: { values: data['artists'].map(function(a) { return a.exhibitorName;})}, },
            {title: 'Artist #', field: 'exhibitorNumber', headerWordWrap: true, headerSort: true, width: 60,
                headerFilter: 'list', headerFilterParams: { values: data['artists'].map(function(a) { return a.exhibitorNumber;})},
                hozAlign: "right",
            },
            {title: 'Item #', field: 'item_key', headerSort: true, headerFilter: true, headerWordWrap: true, width: 60, hozAlign: "right",},
            {title: 'Type', field: 'type', headerSort: true, headerFilter: 'list', headerFilterParams: { values: ['art', 'print', 'nfs']}, width: 75, },
            {title: 'Title', field: 'title', headerSort: true, headerFilter: true,},
            {title: 'Min Bid or Ins.', field: 'min_price', headerSort: true, headerFilter: true, headerWordWrap: true, width: 100, formatter: "money", hozAlign: "right", },
            {title: 'Q. Sale or Print', field: 'sale_price', headerSort: true, headerFilter: true, headerWordWrap: true, width: 100, formatter: "money", hozAlign: "right", },
            {title: 'Orig Qty', field: 'original_qty', headerSort: true, headerFilter: true, headerWordWrap: true, width: 70, hozAlign: "right", },
            {title: 'Current Qty', field: 'quantity', headerSort: true, headerFilter: true, headerWordWrap: true, width: 70, hozAlign: "right", },
            {title: 'Status', field: 'status', headerSort: true, headerFilter:'list', headerFilterParams: { values: ['Not In Show', 'Checked In', 'BID', 'Quicksale/Sold', 'Removed from Show', 'Purchased/Released', 'To Auction', 'Sold Bid Sheet', 'Checked Out']}, },
            {title: 'Location', field: 'location', headerSort: true, headerFilter: true, },
            {title: 'Bidder', field: 'bidderText', headerSort: true, headerFilter:true, },
            {title: 'Sale Price', field: 'final_price', headerSort: true, headerFilter: true, headerWordWrap: true, width: 100, formatter: "money", hozAlign: "right", },
        ]
    });
}
