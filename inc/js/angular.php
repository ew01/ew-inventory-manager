<?php
/**
 * Created by PhpStorm.
 * Author: davidellenburg
 * Date: 1/24/17
 * Time: 11:48 AM
 * Name:
 * Desc:
 */
/** @noinspection PhpUndefinedVariableInspection */




?>
<script>
    angular.module('<?= ($ewim_angularApp != '' ? $ewim_angularApp : 'listApp'); ?>', ['ui.bootstrap'])
        .controller('<?= ($ewim_angularController != '' ? $ewim_angularController : 'listController'); ?>',
            function($scope, $sce) {
                $scope.orderByField= "<?= ($ewim_defaultOrderBy != '' ? $ewim_defaultOrderBy : ''); ?>"; // set the default sort type
                $scope.reverseSort= false;  // set the default sort order

                //create the list
                $scope.data= <?= ($ewim_jsonData != '' ? $ewim_jsonData : '[{"none":"none"}]'); ?>;

                <?php if($ewim_usePagination != 0){?>
                //Pagination Setup
                $scope.filteredData= [];
                $scope.currentPage= 1;
                $scope.numPerPage= <?= ($ewim_ajsResultsPerPage != '' ? $ewim_ajsResultsPerPage : '10'); ?>;
                $scope.numPages= 5;
                $scope.maxSize= 5;

                /*
                $scope.numPages = function () {
                    return Math.ceil($scope.data.length / $scope.numPerPage);
                };
                */

                $scope.$watch('currentPage + numPerPage',
                    function() {
                        var begin = (($scope.currentPage - 1) * $scope.numPerPage);
                        var end = begin + $scope.numPerPage;
                        $scope.filteredData = $scope.data.slice(begin, end);
                    }
                );
                <?php } ?>
            }
        );
</script>

