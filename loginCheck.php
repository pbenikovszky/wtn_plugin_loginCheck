<?php
defined('_JEXEC') or die('Access Denied');

class plgUserLoginCheck extends JPlugin
{

    public function onUserAfterLogin($options = array())
    {

        $myMsg = 0;

        $app = JFactory::getApplication();
        $username = $options['user']->username;
        $user_id = $options['user']->id;
        $statusCode = $this->params->get('statusCode');
        $engageBoxID = $this->params->get('engageBoxID');
        $shopperGroupID = $this->params->get('shopperGroupID');
        $limitLow = $this->params->get('limitLow');
        $limitHigh = $this->params->get('limitHigh');

        $db = JFactory::getDBO();

        $query = 'SELECT v.virtuemart_shoppergroup_id FROM #__virtuemart_vmuser_shoppergroups v WHERE v.virtuemart_user_id =' . $db->quote($user_id);
        $db->setQuery($query);
        $result = $db->loadResult();

        if ($result == $shopperGroupID) {

            // get the orders where Status Code is not 'Kisker Fizetett' and sum the totals
            $query =
            "SELECT
                    o.virtuemart_user_id, o.virtuemart_order_id, h.order_status_code, o.order_total, MAX(h.created_on)
                 FROM
                    #__virtuemart_orders o
                 LEFT JOIN
                    #__virtuemart_order_histories h ON o.virtuemart_order_id = h.virtuemart_order_id
                WHERE
                    virtuemart_user_id = " . $db->quote($user_id) . " AND
                    o.order_status = " . $db->quote($statusCode) . " AND
                    h.order_status_code = " . $db->quote($statusCode) . "
                GROUP BY
                    o.virtuemart_order_id
                HAVING
                     (MAX(h.created_on) < DATE_ADD(NOW(), INTERVAL - " . $limitLow . " DAY) AND o.order_total < 2000000) OR
                     (MAX(h.created_on) < DATE_ADD(NOW(), INTERVAL - " . $limitHigh . " DAY) AND o.order_total > 2000000)";

            $db->setQuery($query);
            $db->query();
            $countOrders = $db->getNumRows();
            if ($countOrders > 0) {
                $session = &JFactory::getSession();
                $session->set('isKisker', "1");
                $session->set('engageBoxID', $engageBoxID);
            }
        }

    }

}
