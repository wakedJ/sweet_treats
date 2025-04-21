<div class="dashboard-cards">
                    <div class="card">
                        <div class="card-icon">⭐</div>
                        <h3 class="card-title">Total Reviews</h3>
                        <div class="card-value">1,245</div>
                        <div class="card-stat positive">↑ 9.3% from last month</div>
                    </div>
                </div>
                
                <div class="table-container">
                    <div class="table-header">
                        <h3 class="table-title">Recent Reviews</h3>
                        <div class="search-container">
                            <span class="search-icon">🔍</span>
                            <input type="text" class="search-input" placeholder="Search reviews...">
                        </div>
                    </div>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Customer</th>
                                <th>Message</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr data-review-id="1">
                                <td>
                                    <div class="customer-info">
                                        <div>Emma Watson</div>
                                    </div>
                                </td>
                                <td class="message-text">Good flavor but they arrived broken. Packaging could be improved.</td>
                                <td>03/09/2025</td>
                                <td>
                                    <div class="action-icons">
                                        <div class="action-icon view-icon" title="View Details" onclick="viewReviewDetails(1)">👁️</div>
                                        <div class="action-icon reply-icon" title="Reply" onclick="replyToReview(1)">💬</div>
                                        <div class="action-icon feature-icon featured" title="Remove from Homepage" 
                                            onclick="toggleFeatureReview(1, this)" data-featured="true">⭐</div>
                                    </div>
                                </td>
                            </tr>
                            <tr data-review-id="2">
                                <td>
                                    <div class="customer-info">
                                        <div>David Thompson</div>
                                    </div>
                                </td>
                                <td class="message-text">Can I pay on delivery?</td>
                                <td>03/08/2025</td>
                                <td>
                                    <div class="action-icons">
                                        <div class="action-icon view-icon" title="View Details" onclick="viewReviewDetails(2)">👁️</div>
                                        <div class="action-icon reply-icon" title="Reply" onclick="replyToReview(2)">💬</div>
                                        <div class="action-icon feature-icon" title="Feature on Homepage" 
                                            onclick="toggleFeatureReview(2, this)" data-featured="false">☆</div>
                                    </div>
                                </td>
                            </tr>
                            <tr data-review-id="3">
                                <td>
                                    <div class="customer-info">
                                        <div>Sophia Rodriguez</div>
                                    </div>
                                </td>
                                <td class="message-text">My order hasn't arrived yet!</td>
                                <td>03/07/2025</td>
                                <td>
                                    <div class="action-icons">
                                        <div class="action-icon view-icon" title="View Details" onclick="viewReviewDetails(3)">👁️</div>
                                        <div class="action-icon reply-icon" title="Reply" onclick="replyToReview(3)">💬</div>
                                        <div class="action-icon feature-icon" title="Feature on Homepage" 
                                            onclick="toggleFeatureReview(3, this)" data-featured="false">☆</div>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>