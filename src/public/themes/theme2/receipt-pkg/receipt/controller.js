app.component('receiptList', {
    templateUrl: receipt_list_template_url,
    controller: function($http, $location, HelperService, $scope, $routeParams, $rootScope, $element, $mdSelect) {
        $scope.loading = true;
        $('#search_receipt').focus();
        $('li').removeClass('active');
        $('.receipt_flink').addClass('active').trigger('click');
        var self = this;
        self.theme = admin_theme;
        self.hasPermission = HelperService.hasPermission;
        if (!self.hasPermission('receipts')) {
            window.location = "#!/page-permission-denied";
            return false;
        }
        $http.get(
            laravel_routes['getReceiptSessionData']
        ).then(function(response) {
            if (response.data.success) {
                self.status = response.data.status;
                self.receipt_of = response.data.receipt_of;
                self.account_code = response.data.account_code;
                self.account_name = response.data.account_name;
                self.config_status = response.data.config_status;
                self.receipt_date = response.data.receipt_date;
                $('#daterange1').val(response.data.receipt_date);
                $('#search_receipt').val(response.data.search_receipt);
            }
        });
        /*if (!self.hasPermission('receipts')) {
            window.location = "#!/page-permission-denied";
            return false;
        }*/
        self.account_search = '';
        if (self.receipt_of == 7621) {
            self.account_search = 'vendors.name';
        } else if (self.receipt_of == 7620) {
            self.account_search = 'customers.name';
        }
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
        setTimeout(function() {
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
                scrollX: true,
                scrollCollapse: true,
                ajax: {
                    url: laravel_routes['getReceiptList'],
                    type: "GET",
                    dataType: "json",
                    data: function(d) {
                        d.receipt_of_id = $("#receipt_of_id").val();
                        d.account_name = $("#account_name").val();
                        d.account_code = $("#account_code").val();
                        d.receipt_number = $('#receipt_number').val();
                        d.receipt_date = $('#daterange1').val();
                        d.config_status = $("#status").val();
                    },
                },
                columns: [
                    { data: 'action', class: 'action', searchable: false },
                    { data: 'receipt_date', searchable: false },
                    { data: 'receipt_number', name: 'receipts.permanent_receipt_no' },
                    { data: 'receipt_of_name', name: 'receipt_ofs.name', searchable: true },
                    { data: 'account_code', searchable: false },
                    { data: 'account_name', searchable: false },
                    { data: 'amount', searchable: false, class: 'text-right' },
                    { data: 'settled_amount', searchable: false, class: 'text-right' },
                    { data: 'balance_amount', searchable: false, class: 'text-right' },
                    { data: 'description', name: 'receipts.description', searchable: true },
                    { data: 'status_name', name: 'configs.name', searchable: true },
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
                // alert($id);
                $('#receipt_id').val($id);
            }
            $scope.deleteConfirm = function() {
                $id = $('#receipt_id').val();
                // console.log($id);
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

            $('#daterange1').on('apply.daterangepicker', function(ev, picker) {
                $(this).val(picker.startDate.format('DD-MM-YYYY') + ' to ' + picker.endDate.format('DD-MM-YYYY'));
                dataTables.fnFilter();
            });
            $('#daterange1').on('cancel.daterangepicker', function(ev, picker) {
                $(this).val('');
            });

            $('#receipt_number').keyup(function() {
                dataTables.fnFilter();
            });
            $('#account_code').keyup(function() {
                dataTables.fnFilter();
            });
            $('#account_name').keyup(function() {
                dataTables.fnFilter();
            });
            $scope.onSelectedReceipt = function(id) {
                $('#receipt_of').val(id);
                dataTables.fnFilter();
            }
            $scope.onSelectedStatus = function(id) {
                $('#status').val(id);
                dataTables.fnFilter();
            }

            $scope.reset_filter = function() {
                $('#receipt_number').val('');
                $('#receipt_of').val('');
                $('#account_code').val('');
                $('#account_name').val('');
                $('#daterange1').val(null);
                $('#status').val('');
                dataTables.fnFilter();
            }

            // var datatables = $('#receipt_list').dataTable();
            // $scope.reset_filter = function() {
            //     self.account_code = '';
            //     self.account_name = '';
            //     self.receipt_number = '';
            //     self.config_status = '';
            //     self.receipt_of_id = '';
            //     $('#daterange1').val(null);
            //     datatables.fnFilter();
            // }
            // $scope.loadDT = function() {
            //     datatables.fnFilter();
            //     $('#search_receipt').focus();

            // }
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
        if (!self.hasPermission('view-receipt')) {
            window.location = "#!/page-permission-denied";
            return false;
        }
        /*self.region_permission = self.hasPermission('regions');
        self.city_permission = self.hasPermission('cities');*/
        self.angular_routes = angular_routes;
        $http.get(
            laravel_routes['getReceiptViewData'], {
                params: {
                    id: $routeParams.id,
                }
            }
        ).then(function(response) {
            self.receipt = response.data.receipt;
            //self.transactions = response.data.transactions;
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