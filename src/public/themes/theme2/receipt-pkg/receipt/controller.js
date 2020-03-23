app.component('receiptList', {
    templateUrl: receipt_list_template_url,
    controller: function($http, $location, HelperService, $scope, $routeParams, $rootScope, $element, $mdSelect) {
        $scope.loading = true;
        var self = this;
        self.theme = admin_theme;
        //  if (!self.hasPermission('receipts')) {
        //     window.location = "#!/page-permission-denied";
        //     return false;
        // }
        $('#search_receipt').focus();
        

        $http.get(
                laravel_routes['getReceiptSessionData']
            ).then(function(response) {
                if(response.data.success){
                    self.status = response.data.status;
                    self.account_code = response.data.account_code;
                    self.account_name = response.data.account_name;
                    self.config_status = response.data.config_status;
                    self.receipt_date = response.data.receipt_date;
                    $('#daterange1').val(response.data.receipt_date);
                    $('#search_receipt').val(response.data.search_receipt);
                }
            });
        self.hasPermission = HelperService.hasPermission;
        /*if (!self.hasPermission('receipts')) {
            window.location = "#!/page-permission-denied";
            return false;
        }*/

        $('.docDatePicker').bootstrapDP({
            endDate: 'today',
            todayHighlight: true
        });

        $('#reference_date').datepicker({
            dateFormat: 'dd-mm-yy',
            maxDate: '0',
            todayHighlight: true,
            autoclose: true
        });
        var table_scroll;
        table_scroll = $('.page-main-content.list-page-content').height() - 37;
        setTimeout(function(){
        var dataTable = $('#receipt_list').DataTable({
            "dom": cndn_dom_structure,
            "language": {
                "search": "",
                "searchPlaceholder": "Search",
                "lengthMenu": "Rows _MENU_",
                "paginate": {
                    "next": '<i class="icon ion-ios-arrow-forward"></i>',
                    "previous": '<i class="icon ion-ios-arrow-back"></i>'
                },
            },
            pageLength: 10,
            processing: true,
            stateSaveCallback: function(settings, data) {
                localStorage.setItem('CDataTables_' + settings.sInstance, JSON.stringify(data));
            },
            stateLoadCallback: function(settings) {
                var state_save_val = JSON.parse(localStorage.getItem('CDataTables_' + settings.sInstance));
                if (state_save_val) {
                    $('#search_receipt').val(state_save_val.search.search);
                }
                return JSON.parse(localStorage.getItem('CDataTables_' + settings.sInstance));
            },
            serverSide: true,
            paging: true,
            stateSave: true,
            scrollY: table_scroll + "px",
            scrollCollapse: true,
            ajax: {
                url: laravel_routes['getReceiptList'],
                type: "GET",
                dataType: "json",
                data: function(d) {
                    d.account_name = self.account_name;
                    d.account_code = self.account_code;
                    d.receipt_number = self.receipt_number;
                    d.receipt_date = $('#daterange1').val();
                    d.config_status = self.config_status;
                },
            },
            columns: [
                { data: 'action', class: 'action', name: 'action', searchable: false },
                { data: 'receipt_date', searchable: false},
                { data: 'receipt_number', name: 'receipts.permanent_receipt_no' },
                { data: 'receipt_of_name', name: 'configs.name' },
                // { data: 'account_code', name: 'customers.code', searchable: false },
                // { data: 'account_name', name: 'customers.name', searchable: false },
                // { data: 'receipt_amount',  searchable: false },
                // { data: 'received_amount',  searchable: false },
                // { data: 'balance_amount',  searchable: false },
                { data: 'description', name: 'receipts.description' },
                { data: 'status_name', name: 'configs.name' },
            ],
            "infoCallback": function(settings, start, end, max, total, pre) {
                $('#table_info').html(total)
                $('.foot_info').html('Showing ' + start + ' to ' + end + ' of ' + max + ' entries')
            },
            rowCallback: function(row, data) {
                $(row).addClass('highlight-row');
            }
        });
        $('.dataTables_length select').select2();
        
        $('.refresh_table').on("click", function() {
            $('#receipt_list').DataTable().ajax.reload();
        });

        $scope.clear_search = function() {
            $('#search_receipt').val('');
            $('#receipt_list').DataTable().search('').draw();
            $('#search_receipt').focus();

        }

        var dataTables = $('#receipt_list').dataTable();
        $("#search_receipt").keyup(function() {
            dataTables.fnFilter(this.value);
        $('#search_receipt').focus();

        });

        //FOCUS ON SEARCH FIELD
        setTimeout(function() {
            $('div.dataTables_filter input').focus();
        }, 2500);

        //DELETE
        $scope.deleteReceipt = function($id) {
            alert();
            $('#receipt_id').val($id);
        }
        $scope.deleteConfirm = function() {
            $id = $('#receipt_id').val();
            $http.get(
                laravel_routes['deleteReceiptData'], {
                    params: {
                        id: $id,
                    }
                }
            ).then(function(response) {
                if (response.data.success) {
                    custom_noty('success', 'Receipt Deleted Successfully');
                    $('#receipt_list').DataTable().ajax.reload();
                    $location.path('/receipt-pkg/receipt/list');
                    $('#search_receipt').focus();

                }
            });
        }
        $element.find('input').on('keydown', function(ev) {
            ev.stopPropagation();
        });
        $scope.clearSearchTerm = function() {
            $scope.searchTerm = '';
            $scope.searchTerm1 = '';
        };
        /* Modal Md Select Hide */
        $('.modal').bind('click', function(event) {
            if ($('.md-select-menu-container').hasClass('md-active')) {
                $mdSelect.hide();
            }
        });

        var datatables = $('#receipt_list').dataTable();
        $scope.reset_filter = function() {
            self.account_code = '';
            self.account_name  = '';
            self.receipt_number = '';
            self.config_status = '';
            $('#daterange1').val(null);
            datatables.fnFilter();
        }
        $scope.loadDT = function (){
            datatables.fnFilter();
            $('#search_receipt').focus();

        }
        $rootScope.loading = false;
        }, 2500);
    }
});

//------------------------------------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------------------------------------
app.component('receiptView', {
    templateUrl: receipt_view_template_url,
    controller: function($http, HelperService, $scope, $routeParams, $rootScope) {
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        // if (!self.hasPermission('view-receipt')) {
        //     window.location = "#!/page-permission-denied";
        //     return false;
        // }
        /*self.region_permission = self.hasPermission('regions');
        self.city_permission = self.hasPermission('cities');*/
        self.angular_routes = angular_routes;
        $http.get(
            laravel_routes['getreceiptViewData'], {
                params: {
                    id: $routeParams.id,
                }
            }
        ).then(function(response) {
            self.receipt = response.data.receipt;
            self.transactions = response.data.transactions;
        });
        /* Tab Funtion */
        $('.btn-nxt').on("click", function() {
            $('.editDetails-tabs li.active').next().children('a').trigger("click");
            tabPaneFooter();
        });
        $('.btn-prev').on("click", function() {
            $('.editDetails-tabs li.active').prev().children('a').trigger("click");
            tabPaneFooter();
        });
        $('.btn-pills').on("click", function() {
            tabPaneFooter();
        });
        $scope.btnNxt = function() {}
        $scope.prev = function() {}
    }
});